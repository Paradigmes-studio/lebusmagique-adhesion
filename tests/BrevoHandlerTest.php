<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/adhesionClient.php';
require_once 'lib/BrevoHandler.php';

class BrevoHandlerTest extends TestCase
{
    private function makeClient(array $overrides = []): AdhesionClient
    {
        $c = new AdhesionClient();
        $c->id = $overrides['id'] ?? 42;
        $c->first_name = $overrides['first_name'] ?? 'Marie';
        $c->last_name = $overrides['last_name'] ?? 'Dupont';
        $c->email = $overrides['email'] ?? 'marie@example.com';
        $c->date_debut = $overrides['date_debut'] ?? '2026-01-10 00:00:00';
        $c->date_fin = $overrides['date_fin'] ?? '2027-01-10 00:00:00';
        $c->referral_source = $overrides['referral_source'] ?? 'passant';
        return $c;
    }

    // --- isConfigured ---

    public function testIsConfiguredFalseWhenEmpty(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => '']);
        $this->assertFalse($h->isConfigured());
    }

    public function testIsConfiguredFalseWhenPlaceholder(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'BREVO_API_KEY']);
        $this->assertFalse($h->isConfigured());
    }

    public function testIsConfiguredTrueWhenSet(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-real-looking-key']);
        $this->assertTrue($h->isConfigured());
    }

    // --- upsertContact early return ---

    public function testUpsertContactReturnsErrorWhenNotConfigured(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => '']);
        $res = $h->upsertContact($this->makeClient(), [3]);
        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('not configured', $res['msg']);
    }

    // --- buildPayload ---

    public function testBuildPayloadCoreAttributes(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $payload = $h->buildPayload($this->makeClient(), [3]);

        $this->assertSame('marie@example.com', $payload['email']);
        $this->assertTrue($payload['updateEnabled']);
        $this->assertSame([3], $payload['listIds']);
        $this->assertArrayNotHasKey('unlinkListIds', $payload);

        $attrs = $payload['attributes'];
        $this->assertSame('Marie', $attrs['PRENOM']);
        $this->assertSame('Dupont', $attrs['NOM']);
        $this->assertSame(42, $attrs['ID_ADHERENT']);
        $this->assertSame('passant', $attrs['SOURCE']);
        $this->assertSame('2026-01-10', $attrs['DATE_ADHESION']);
        $this->assertSame('2027-01-10', $attrs['DATE_FIN']);
    }

    public function testBuildPayloadDatesFormattedToIso(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $client = $this->makeClient([
            'date_debut' => '2026-03-20 14:35:00',
            'date_fin' => '2027-03-20 14:35:00',
        ]);
        $payload = $h->buildPayload($client, [3]);
        $this->assertSame('2026-03-20', $payload['attributes']['DATE_ADHESION']);
        $this->assertSame('2027-03-20', $payload['attributes']['DATE_FIN']);
    }

    public function testBuildPayloadOmitsEmptyOptionalAttributes(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $client = new AdhesionClient();
        $client->email = 'noinfo@example.com';
        $payload = $h->buildPayload($client, [3]);

        $attrs = $payload['attributes'];
        $this->assertArrayNotHasKey('ID_ADHERENT', $attrs);
        $this->assertArrayNotHasKey('SOURCE', $attrs);
        $this->assertArrayNotHasKey('DATE_ADHESION', $attrs);
        $this->assertArrayNotHasKey('DATE_FIN', $attrs);
        $this->assertSame('', $attrs['PRENOM']);
        $this->assertSame('', $attrs['NOM']);
    }

    public function testBuildPayloadSubscribeAddsBothLists(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $payload = $h->buildPayload($this->makeClient(), [3, 4]);
        $this->assertSame([3, 4], $payload['listIds']);
        $this->assertArrayNotHasKey('unlinkListIds', $payload);
    }

    public function testBuildPayloadUnsubscribeUnlinksNewsletter(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $payload = $h->buildPayload($this->makeClient(), [3], [4]);
        $this->assertSame([3], $payload['listIds']);
        $this->assertSame([4], $payload['unlinkListIds']);
    }

    public function testBuildPayloadCoercesListIdsToInt(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $payload = $h->buildPayload($this->makeClient(), ['3', '4'], ['5']);
        $this->assertSame([3, 4], $payload['listIds']);
        $this->assertSame([5], $payload['unlinkListIds']);
    }

    public function testBuildPayloadIncludesEditToken(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $client = $this->makeClient();
        $client->edit_token = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';
        $payload = $h->buildPayload($client, [3]);
        $this->assertSame('a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6', $payload['attributes']['EDIT_TOKEN']);
    }

    public function testBuildPayloadOmitsEditTokenWhenEmpty(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $client = $this->makeClient();
        $payload = $h->buildPayload($client, [3]);
        $this->assertArrayNotHasKey('EDIT_TOKEN', $payload['attributes']);
    }

    // --- getContact / isInList ---

    public function testGetContactReturnsErrorWhenNotConfigured(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => '']);
        $res = $h->getContact('marie@example.com');
        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('not configured', $res['msg']);
    }

    public function testIsInListReturnsNullWhenNotConfigured(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => '']);
        $this->assertNull($h->isInList('marie@example.com', 4));
    }

    public function testIsInListReturnsNullWhenEmailEmpty(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $this->assertNull($h->isInList('', 4));
    }

    public function testIsInListReturnsNullWhenListIdEmpty(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $this->assertNull($h->isInList('marie@example.com', 0));
    }

    public function testParseListIdsFromBodyExtractsIntegers(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $body = json_encode(['email' => 'x@y.z', 'listIds' => [3, '4', 5]]);
        $this->assertSame([3, 4, 5], $h->parseListIdsFromBody($body));
    }

    public function testParseListIdsFromBodyReturnsEmptyWhenMissing(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $this->assertSame([], $h->parseListIdsFromBody(json_encode(['email' => 'x@y.z'])));
    }

    public function testParseListIdsFromBodyReturnsEmptyOnInvalidJson(): void
    {
        $h = new BrevoHandler(null, ['brevoApiKey' => 'xkeysib-x']);
        $this->assertSame([], $h->parseListIdsFromBody('not json'));
        $this->assertSame([], $h->parseListIdsFromBody(''));
    }
}
