<?php

use App\Models\ApiToken;
use App\Models\Commune;
use App\Models\Customer;
use App\Models\Region;

function validCustomerPayload(Commune $commune, array $overrides = []): array
{
    return [
        'dni' => '12345678-9',
        'id_reg' => $commune->id_reg,
        'id_com' => $commune->id_com,
        'email' => 'maria@example.com',
        'name' => 'María',
        'last_name' => 'González',
        'address' => 'Av. Bolívar, Edif. Caroní, Piso 3',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->token = ApiToken::factory()->create()->token;
});

describe('registro de customers', function () {
    test('registra un customer activo', function () {
        $commune = Commune::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson('/api/customers', validCustomerPayload($commune));

        $response->assertCreated()->assertJson([
            'success' => true,
            'message' => 'Customer registrado con éxito',
            'data' => [
                'dni' => '12345678-9',
                'email' => 'maria@example.com',
                'status' => 'A',
                'region' => $commune->region->description,
                'commune' => $commune->description,
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'dni' => '12345678-9',
            'id_reg' => $commune->id_reg,
            'id_com' => $commune->id_com,
            'status' => 'A',
        ]);
    });

    test('permite registrar sin dirección', function () {
        $commune = Commune::factory()->create();

        $payload = validCustomerPayload($commune);
        unset($payload['address']);

        $this->withToken($this->token)
            ->postJson('/api/customers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.address', null);
    });

    test('rechaza una comuna que no pertenece a la región', function () {
        $region = Region::factory()->create();
        $communeFromOtherRegion = Commune::factory()->create();

        $response = $this->withToken($this->token)->postJson('/api/customers', validCustomerPayload(
            $communeFromOtherRegion,
            ['id_reg' => $region->id_reg],
        ));

        $response->assertUnprocessable()->assertJsonPath('success', false);

        expect($response->json('data'))->toHaveKey('id_com');
    });

    test('rechaza región o comuna inexistente', function (array $overrides, string $failingField) {
        $commune = Commune::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson('/api/customers', validCustomerPayload($commune, $overrides));

        $response->assertUnprocessable()->assertJsonPath('success', false);

        expect($response->json('data'))->toHaveKey($failingField);
    })->with([
        'región inexistente' => [['id_reg' => 9999], 'id_reg'],
        'comuna inexistente' => [['id_com' => 9999], 'id_com'],
    ]);

    test('rechaza comunas inactivas o eliminadas', function (string $state) {
        $commune = Commune::factory()->{$state}()->create();

        $response = $this->withToken($this->token)
            ->postJson('/api/customers', validCustomerPayload($commune));

        $response->assertUnprocessable()->assertJsonPath('success', false);
    })->with(['inactive', 'trash']);

    test('rechaza dni y email duplicados', function () {
        $existing = Customer::factory()->create();
        $commune = Commune::factory()->create();

        $response = $this->withToken($this->token)->postJson('/api/customers', validCustomerPayload($commune, [
            'dni' => $existing->dni,
            'email' => $existing->email,
        ]));

        $response->assertUnprocessable()->assertJsonPath('success', false);

        expect($response->json('data'))->toHaveKeys(['dni', 'email']);
    });

    test('exige el token de autenticación', function () {
        $commune = Commune::factory()->create();

        $this->postJson('/api/customers', validCustomerPayload($commune))
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    });
});

describe('consulta de customers', function () {
    test('devuelve un customer activo por dni', function () {
        $customer = Customer::factory()->create();

        $this->withToken($this->token)
            ->getJson('/api/customers?dni='.$customer->dni)
            ->assertSuccessful()
            ->assertExactJson([
                'success' => true,
                'message' => 'Customer encontrado',
                'data' => [
                    'name' => $customer->name,
                    'last_name' => $customer->last_name,
                    'address' => $customer->address,
                    'region' => $customer->region->description,
                    'commune' => $customer->commune->description,
                ],
            ]);
    });

    test('devuelve un customer activo por email', function () {
        $customer = Customer::factory()->create();

        $this->withToken($this->token)
            ->getJson('/api/customers?email='.$customer->email)
            ->assertSuccessful()
            ->assertJsonPath('data.name', $customer->name);
    });

    test('devuelve address null cuando no tiene dirección', function () {
        $customer = Customer::factory()->create(['address' => null]);

        $this->withToken($this->token)
            ->getJson('/api/customers?dni='.$customer->dni)
            ->assertSuccessful()
            ->assertJsonPath('data.address', null);
    });

    test('no devuelve customers inactivos ni eliminados', function (string $state) {
        $customer = Customer::factory()->{$state}()->create();

        $this->withToken($this->token)
            ->getJson('/api/customers?dni='.$customer->dni)
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Customer no encontrado', 'data' => null]);
    })->with(['inactive', 'trash']);

    test('responde 404 si el customer no existe', function () {
        $this->withToken($this->token)
            ->getJson('/api/customers?dni=00000000-0')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    });

    test('exige dni o email', function () {
        $this->withToken($this->token)
            ->getJson('/api/customers')
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Parámetros de búsqueda inválidos']);
    });

    test('exige el token de autenticación', function () {
        $this->getJson('/api/customers?dni=12345678-9')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    });
});

describe('eliminación de customers', function () {
    test('elimina lógicamente un customer activo', function () {
        $customer = Customer::factory()->create();

        $this->withToken($this->token)
            ->deleteJson('/api/customers?dni='.$customer->dni)
            ->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Customer eliminado con éxito',
                'data' => ['dni' => $customer->dni, 'status' => 'T'],
            ]);

        $this->assertDatabaseHas('customers', ['dni' => $customer->dni, 'status' => 'T']);
    });

    test('elimina lógicamente un customer desactivado', function () {
        $customer = Customer::factory()->inactive()->create();

        $this->withToken($this->token)
            ->deleteJson('/api/customers?email='.$customer->email)
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'T');

        $this->assertDatabaseHas('customers', ['dni' => $customer->dni, 'status' => 'T']);
    });

    test('responde Registro no existe si ya estaba eliminado', function () {
        $customer = Customer::factory()->trash()->create();

        $this->withToken($this->token)
            ->deleteJson('/api/customers?dni='.$customer->dni)
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Registro no existe', 'data' => null]);
    });

    test('responde Registro no existe si el customer no existe', function () {
        $this->withToken($this->token)
            ->deleteJson('/api/customers?dni=00000000-0')
            ->assertNotFound()
            ->assertJson(['success' => false, 'message' => 'Registro no existe']);
    });

    test('exige dni o email', function () {
        $this->withToken($this->token)
            ->deleteJson('/api/customers')
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    });

    test('exige el token de autenticación', function () {
        $this->deleteJson('/api/customers?dni=12345678-9')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    });
});
