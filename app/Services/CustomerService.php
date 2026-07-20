<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerService
{
    /**
     * Registra un customer activo y lo devuelve con su región y comuna cargadas.
     *
     * @param  array{dni: string, id_reg: int, id_com: int, email: string, name: string, last_name: string, address?: string|null}  $data
     */
    public function register(array $data): Customer
    {
        $customer = Customer::query()->create([
            ...$data,
            'date_reg' => now(),
        ]);

        return $customer->load(['region', 'commune']);
    }

    /**
     * Busca un customer por dni y/o email, pero solo entre los activos (A).
     */
    public function findActive(?string $dni, ?string $email): ?Customer
    {
        return $this->queryByIdentifier($dni, $email)
            ->active()
            ->with(['region', 'commune'])
            ->first();
    }

    /**
     * Borrado lógico: el customer pasa de A o I a T.
     * Devuelve null si no existe o si ya estaba eliminado.
     */
    public function delete(?string $dni, ?string $email): ?Customer
    {
        $customer = $this->queryByIdentifier($dni, $email)
            ->whereIn('status', [Status::Active->value, Status::Inactive->value])
            ->first();

        if ($customer === null) {
            return null;
        }

        $customer->update(['status' => Status::Trash]);

        return $customer;
    }

    /**
     * @return Builder<Customer>
     */
    private function queryByIdentifier(?string $dni, ?string $email): Builder
    {
        return Customer::query()
            ->when($dni !== null, fn (Builder $query) => $query->where('dni', $dni))
            ->when($email !== null, fn (Builder $query) => $query->where('email', $email));
    }
}
