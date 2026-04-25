<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $companyCnpj = '00000000000000';
        $companyId = DB::table('companies')->where('cnpj', $companyCnpj)->value('id');
        if (! $companyId) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'EMPRESA DEMONSTRAÇÃO',
                'legal_name' => 'EMPRESA DEMONSTRAÇÃO',
                'cnpj' => $companyCnpj,
                'code' => 'DEMO0001',
                'email' => 'demo@aqamed.test',
                'phone' => '0000000000',
                'active' => true,
                'is_demo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('companies')->where('id', $companyId)->update([
                'name' => 'EMPRESA DEMONSTRAÇÃO',
                'legal_name' => 'EMPRESA DEMONSTRAÇÃO',
                'cnpj' => $companyCnpj,
                'code' => 'DEMO0001',
                'email' => 'demo@aqamed.test',
                'phone' => '0000000000',
                'active' => true,
                'is_demo' => true,
                'updated_at' => $now,
            ]);
        }

        $adminRoleId = $this->upsertRole($companyId, 'Admin', 'Acesso total', true, $now);
        $secretaryRoleId = $this->upsertRole($companyId, 'Secretaria', 'Agenda e atendimentos', false, $now);
        $professionalRoleId = $this->upsertRole($companyId, 'Profissional', 'Atendimentos e agenda', false, $now);

        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $adminRoleId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $secretaryPermissionKeys = [
            'cadastro.pacientes.view',
            'agendamento.agenda.view',
            'agendamento.agendamentos.view',
            'atendimento.agenda.view',
            'atendimento.atendimentos.view',
            'financeiro.contas_receber.view',
        ];
        $this->syncRolePermissions($secretaryRoleId, $secretaryPermissionKeys, $now);

        $professionalPermissionKeys = [
            'cadastro.pacientes.view',
            'agendamento.agenda.view',
            'agendamento.agendamentos.view',
            'atendimento.agenda.view',
            'atendimento.atendimentos.view',
        ];
        $this->syncRolePermissions($professionalRoleId, $professionalPermissionKeys, $now);

        $users = [
            [
                'name' => 'Admin Demo',
                'username' => 'admin',
                'email' => 'admin.demo@aqamed.test',
                'password' => 'demo123',
                'role_id' => $adminRoleId,
                'is_master' => true,
            ],
            [
                'name' => 'Secretaria Demo',
                'username' => 'secretaria',
                'email' => 'secretaria.demo@aqamed.test',
                'password' => 'demo123',
                'role_id' => $secretaryRoleId,
                'is_master' => false,
            ],
            [
                'name' => 'Profissional Demo 1',
                'username' => 'profissional1',
                'email' => 'profissional1.demo@aqamed.test',
                'password' => 'demo123',
                'role_id' => $professionalRoleId,
                'is_master' => false,
            ],
            [
                'name' => 'Profissional Demo 2',
                'username' => 'profissional2',
                'email' => 'profissional2.demo@aqamed.test',
                'password' => 'demo123',
                'role_id' => $professionalRoleId,
                'is_master' => false,
            ],
        ];

        $userIds = [];
        foreach ($users as $user) {
            $userId = DB::table('users')->where('email', $user['email'])->value('id');
            if (! $userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'is_platform_admin' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update([
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'updated_at' => $now,
                ]);
            }

            DB::table('company_user')->updateOrInsert(
                ['company_id' => $companyId, 'user_id' => $userId],
                [
                    'role_id' => $user['role_id'],
                    'is_master' => $user['is_master'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $userIds[$user['username']] = $userId;
        }

        $clinicId = DB::table('clinics')
            ->where('company_id', $companyId)
            ->where('name', 'CLINICA DEMOSTRAÇÃO')
            ->value('id');

        if (! $clinicId) {
            $clinicId = DB::table('clinics')->insertGetId([
                'company_id' => $companyId,
                'name' => 'CLINICA DEMOSTRAÇÃO',
                'legal_name' => 'CLINICA DEMOSTRAÇÃO',
                'cnpj' => $companyCnpj,
                'email' => 'clinica.demo@aqamed.test',
                'phone' => '0000000000',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $unitIds = [];
        foreach (['UNIDADE DEMO 1', 'UNIDADE DEMO 2'] as $unitName) {
            $unitId = DB::table('units')
                ->where('clinic_id', $clinicId)
                ->where('name', $unitName)
                ->value('id');

            if (! $unitId) {
                $unitId = DB::table('units')->insertGetId([
                    'clinic_id' => $clinicId,
                    'name' => $unitName,
                    'address_line1' => 'Rua Demo, 000',
                    'address_line2' => null,
                    'city' => 'Cidade Demo',
                    'state' => 'SP',
                    'zip' => '00000-000',
                    'country' => 'BR',
                    'phone' => '0000000000',
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $unitIds[] = $unitId;
        }

        $professionals = [
            [
                'user_id' => $userIds['profissional1'] ?? null,
                'display_name' => 'Profissional Demo 1',
                'crm_number' => '00001',
                'crm_state' => 'SP',
            ],
            [
                'user_id' => $userIds['profissional2'] ?? null,
                'display_name' => 'Profissional Demo 2',
                'crm_number' => '00002',
                'crm_state' => 'SP',
            ],
        ];

        foreach ($professionals as $professional) {
            if (! $professional['user_id']) {
                continue;
            }

            $professionalId = DB::table('professionals')->where('user_id', $professional['user_id'])->value('id');
            if (! $professionalId) {
                $professionalId = DB::table('professionals')->insertGetId([
                    'user_id' => $professional['user_id'],
                    'display_name' => $professional['display_name'],
                    'crm_number' => $professional['crm_number'],
                    'crm_state' => $professional['crm_state'],
                    'rqe' => null,
                    'bio' => 'Profissional demonstracao.',
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('professionals')->where('id', $professionalId)->update([
                    'display_name' => $professional['display_name'],
                    'crm_number' => $professional['crm_number'],
                    'crm_state' => $professional['crm_state'],
                    'updated_at' => $now,
                ]);
            }

            foreach ($unitIds as $unitId) {
                DB::table('professional_unit')->updateOrInsert(
                    ['professional_id' => $professionalId, 'unit_id' => $unitId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $companyCnpj = '00000000000000';
        $companyId = DB::table('companies')->where('cnpj', $companyCnpj)->value('id');
        if (! $companyId) {
            return;
        }

        $clinicId = DB::table('clinics')
            ->where('company_id', $companyId)
            ->where('name', 'CLINICA DEMOSTRAÇÃO')
            ->value('id');

        if ($clinicId) {
            $unitIds = DB::table('units')->where('clinic_id', $clinicId)->pluck('id')->all();
            if (! empty($unitIds)) {
                DB::table('professional_unit')->whereIn('unit_id', $unitIds)->delete();
                DB::table('units')->whereIn('id', $unitIds)->delete();
            }

            DB::table('clinics')->where('id', $clinicId)->delete();
        }

        $userEmails = [
            'admin.demo@aqamed.test',
            'secretaria.demo@aqamed.test',
            'profissional1.demo@aqamed.test',
            'profissional2.demo@aqamed.test',
        ];
        $userIds = DB::table('users')->whereIn('email', $userEmails)->pluck('id')->all();
        if (! empty($userIds)) {
            DB::table('company_user')->where('company_id', $companyId)->whereIn('user_id', $userIds)->delete();
            DB::table('professionals')->whereIn('user_id', $userIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        $roleIds = DB::table('roles')->where('company_id', $companyId)->pluck('id')->all();
        if (! empty($roleIds)) {
            DB::table('permission_role')->whereIn('role_id', $roleIds)->delete();
            DB::table('roles')->whereIn('id', $roleIds)->delete();
        }

        DB::table('companies')->where('id', $companyId)->delete();
    }

    private function upsertRole(int $companyId, string $name, string $description, bool $isDefault, $now): int
    {
        $roleId = DB::table('roles')
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->value('id');

        if (! $roleId) {
            return DB::table('roles')->insertGetId([
                'company_id' => $companyId,
                'name' => $name,
                'description' => $description,
                'is_default' => $isDefault,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('roles')->where('id', $roleId)->update([
            'description' => $description,
            'is_default' => $isDefault,
            'updated_at' => $now,
        ]);

        return $roleId;
    }

    private function syncRolePermissions(int $roleId, array $permissionKeys, $now): void
    {
        $permissionIds = DB::table('permissions')->whereIn('key', $permissionKeys)->pluck('id')->all();
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }
};
