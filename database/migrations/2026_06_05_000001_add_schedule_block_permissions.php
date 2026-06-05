<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'view' => 'Visualizar bloqueios',
            'create' => 'Criar bloqueios',
            'update' => 'Editar bloqueios',
            'delete' => 'Excluir bloqueios',
        ];

        foreach ($permissions as $action => $label) {
            DB::table('permissions')->updateOrInsert(
                ['key' => "agendamento.bloqueios.{$action}"],
                [
                    'module' => 'agendamento',
                    'resource' => 'bloqueios',
                    'action' => $action,
                    'label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('key', collect(array_keys($permissions))->map(fn ($action) => "agendamento.bloqueios.{$action}"))
            ->pluck('id');

        $masterRoleIds = DB::table('company_user')
            ->where('is_master', true)
            ->whereNotNull('role_id')
            ->pluck('role_id')
            ->unique();

        foreach ($masterRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('key', 'like', 'agendamento.bloqueios.%')
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
