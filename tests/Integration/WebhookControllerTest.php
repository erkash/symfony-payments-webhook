<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Payments\Application\Message\WebhookEventMessage;
use App\Payments\Infrastructure\Messenger\WebhookEventHandler;
use Doctrine\DBAL\Exception;
use JsonException;
use Symfony\Component\Uid\Uuid;

final class WebhookControllerTest extends DatabaseTestCase
{
	/**
	 * @throws JsonException|Exception
	 */
	public function testDuplicateWebhookIsDeduplicatedByProviderAndExternalId(): void
    {
        $client = $this->client;
        self::assertNotNull($client);

        $paymentId = $this->createPayment($client);

        $payload = [
            'externalId' => 'evt-dup-1',
            'paymentId' => $paymentId,
            'status' => 'authorized',
        ];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $rawPayload, 'test');

        $client->request('POST', '/api/webhooks/stripe', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
        ], $rawPayload);

        self::assertResponseStatusCodeSame(202);
        $first = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($first);

        $client->request('POST', '/api/webhooks/stripe', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
        ], $rawPayload);

        self::assertResponseStatusCodeSame(202);
        $second = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($second);

        self::assertSame($first['id'], $second['id']);
        self::assertSame(1, $this->countRows('webhook_events'));

        $client->request('GET', '/api/payments/'.$paymentId);
        self::assertResponseIsSuccessful();
        $payment = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('authorized', $payment['status']);
    }

	/**
	 * @throws JsonException
	 */
	public function testWebhookHandlerSkipsAlreadyProcessedEventOnRetry(): void
    {
        $client = $this->client;
        self::assertNotNull($client);

        $paymentId = $this->createPayment($client);

        $payload = [
            'externalId' => 'evt-retry-1',
            'paymentId' => $paymentId,
            'status' => 'authorized',
        ];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $rawPayload, 'test');

        $client->request('POST', '/api/webhooks/stripe', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
        ], $rawPayload);
        self::assertResponseStatusCodeSame(202);

        $response = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        $eventId = $response['id'];

        $before = $this->fetchWebhookByExternalId('evt-retry-1');
        self::assertNotNull($before);
        self::assertSame(1, (int) $before['signature_valid']);
        self::assertNotNull($before['processed_at']);

        sleep(1);
        $handler = self::getContainer()->get(WebhookEventHandler::class);
        self::assertInstanceOf(WebhookEventHandler::class, $handler);
        $handler(new WebhookEventMessage(Uuid::fromString($eventId)));

        $after = $this->fetchWebhookByExternalId('evt-retry-1');
        self::assertNotNull($after);
        self::assertSame($before['processed_at'], $after['processed_at']);
    }

	/**
	 * @throws JsonException|Exception
	 */
	public function testInvalidSignatureWebhookIsStoredAndDoesNotUpdatePayment(): void
    {
        $client = $this->client;
        self::assertNotNull($client);

        $paymentId = $this->createPayment($client);

        $payload = [
            'externalId' => 'evt-invalid-1',
            'paymentId' => $paymentId,
            'status' => 'succeeded',
        ];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        $client->request('POST', '/api/webhooks/stripe', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => 'invalid-signature',
        ], $rawPayload);

        self::assertResponseStatusCodeSame(202);
        $response = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertFalse($response['signatureValid']);

        $client->request('GET', '/api/payments/'.$paymentId);
        self::assertResponseIsSuccessful();
        $payment = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pending', $payment['status']);

        self::assertSame(1, $this->countRows('webhook_events'));

        $row = $this->fetchWebhookByExternalId('evt-invalid-1');
        self::assertNotNull($row);
        self::assertSame(0, (int) $row['signature_valid']);
        self::assertSame('invalid-signature', $row['signature']);
        self::assertSame($rawPayload, $row['raw_payload']);
        self::assertNotNull($row['processed_at']);
    }

	/**
	 * @throws JsonException
	 */
	private function createPayment($client): string
    {
        $client->request('POST', '/api/payments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'amount' => 1200,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $payment = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payment);

        return $payment['id'];
    }

	/**
	 * @throws Exception
	 */
	private function countRows(string $table): int
    {
        $connection = $this->entityManager?->getConnection();
        self::assertNotNull($connection);

        return (int) $connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
    }

    private function fetchWebhookByExternalId(string $externalId): ?array
    {
        $connection = $this->entityManager?->getConnection();
        self::assertNotNull($connection);

        $row = $connection->fetchAssociative(
            'SELECT external_id, signature_valid, signature, raw_payload, processed_at FROM webhook_events WHERE external_id = :externalId LIMIT 1',
            ['externalId' => $externalId],
        );

        return $row === false ? null : $row;
    }
}
