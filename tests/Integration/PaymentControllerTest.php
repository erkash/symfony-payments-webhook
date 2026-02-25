<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\Exception;
use JsonException;

final class PaymentControllerTest extends DatabaseTestCase
{
	/**
	 * @throws JsonException|Exception
	 */
	public function testCreatePaymentWithoutIdempotencyKeyCreatesTwoPayments(): void
    {
        $client = $this->client;
        self::assertNotNull($client);
        $payload = json_encode([
            'amount' => 2500,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR);

        $client->request('POST', '/api/payments', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('false', $client->getResponse()->headers->get('Idempotency-Replayed'));
        $first = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($first);
        self::assertArrayHasKey('id', $first);
        self::assertArrayHasKey('status', $first);

        $client->request('POST', '/api/payments', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('false', $client->getResponse()->headers->get('Idempotency-Replayed'));
        $second = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($second);
        self::assertArrayHasKey('id', $second);

        self::assertNotSame($first['id'], $second['id']);
        self::assertSame(2, $this->countRows('payments'));
    }

	/**
	 * @throws \JsonException|Exception
	 */
	public function testCreatePaymentWithIdempotencyKeyReplaysAndDoesNotDuplicate(): void
    {
        $client = $this->client;
        self::assertNotNull($client);
        $payload = json_encode([
            'amount' => 5000,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_IDEMPOTENCY_KEY' => 'idemp-create-5000-usd',
        ];

        $client->request('POST', '/api/payments', [], [], $headers, $payload);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('false', $client->getResponse()->headers->get('Idempotency-Replayed'));
        $first = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($first);
        self::assertArrayHasKey('id', $first);

        $client->request('POST', '/api/payments', [], [], $headers, $payload);
        self::assertResponseStatusCodeSame(200);
        self::assertSame('true', $client->getResponse()->headers->get('Idempotency-Replayed'));
        $second = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($second);
        self::assertArrayHasKey('id', $second);

        self::assertSame($first['id'], $second['id']);
        self::assertSame(1, $this->countRows('payments'));
        self::assertSame(1, $this->countRows('payment_idempotency_keys'));
    }

    public function testGetPaymentByIdReturnsCreatedPayment(): void
    {
        $client = $this->client;
        self::assertNotNull($client);
        $client->request('POST', '/api/payments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'amount' => 2500,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);

        $client->request('GET', '/api/payments/'.$data['id']);
        self::assertResponseIsSuccessful();
    }

    public function testCreatePaymentValidationFails(): void
    {
        $client = $this->client;
        self::assertNotNull($client);
        $client->request('POST', '/api/payments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'amount' => 0,
            'currency' => 'XX',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertArrayHasKey('errors', $data);
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
}
