<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Professional;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['code' => 'AQATENDE01'],
            [
                'name' => 'AQAtende',
                'legal_name' => 'AQAtende Salão Demonstração LTDA',
                'cnpj' => '00000000000191',
                'email' => 'contato@aqatende.com',
                'phone' => '+55 31 3000-0000',
                'active' => true,
            ]
        );
        $this->applyDefaultLogo($company);

        $clinic = Clinic::updateOrCreate(
            ['company_id' => $company->id, 'cnpj' => '00.000.000/0001-00'],
            [
                'name' => 'Salão Demonstração',
                'legal_name' => 'AQAtende Salão Demonstração LTDA',
                'email' => 'contato@aqatende.com',
                'phone' => '+55 31 3000-0000',
                'active' => true,
                'terms_version' => config('terms.version'),
                'terms_accepted_at' => now(),
            ]
        );

        $unit = Unit::updateOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Unidade Central'],
            [
                'address_line1' => 'Rua Maranhão, 352',
                'address_line2' => 'Sala 502',
                'city' => 'Belo Horizonte',
                'state' => 'MG',
                'zip' => '30150-330',
                'country' => 'BR',
                'phone' => '+55 31 3000-0001',
                'active' => true,
            ]
        );

        $financialCategories = [
            ['name' => 'Serviços', 'type' => 'receber'],
            ['name' => 'Produtos', 'type' => 'receber'],
            ['name' => 'Comandas', 'type' => 'receber'],
            ['name' => 'Salarios', 'type' => 'pagar'],
            ['name' => 'Fornecedores', 'type' => 'pagar'],
            ['name' => 'Impostos', 'type' => 'pagar'],
        ];

        foreach ($financialCategories as $category) {
            FinancialCategory::updateOrCreate(
                ['clinic_id' => $clinic->id, 'name' => $category['name']],
                ['type' => $category['type'], 'active' => true]
            );
        }

        FinancialAccount::updateOrCreate(
            [
                'clinic_id' => $clinic->id,
                'unit_id' => $unit->id,
                'name' => 'Caixa Principal',
            ],
            ['type' => 'caixa', 'initial_balance_cents' => 0, 'active' => true]
        );

        $specialties = [
            'Cabelo',
            'Manicure',
            'Estética',
        ];

        $specialtyModels = collect($specialties)->map(function (string $name) use ($company) {
            return Specialty::updateOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['active' => true]
            );
        });

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@aqatende.local'],
            [
                'name' => 'Admin AQAtende',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );

        $ownerUser = User::updateOrCreate(
            ['email' => 'sdronsh@gmail.com'],
            [
                'name' => 'S Dronsh',
                'username' => 'sdronsh@gmail.com',
                'password' => Hash::make('senha123'),
                'is_platform_admin' => true,
            ]
        );

        $professionalUser = User::updateOrCreate(
            ['email' => 'profissional@aqatende.local'],
            [
                'name' => 'Mariana Silva',
                'username' => 'mariana',
                'password' => Hash::make('admin123'),
            ]
        );

        $patientUser = User::updateOrCreate(
            ['email' => 'cliente@aqatende.local'],
            [
                'name' => 'Cliente Demo',
                'username' => 'cliente',
                'password' => Hash::make('admin123'),
            ]
        );

        $permissions = $this->seedPermissions();

        $adminRole = Role::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Admin'],
            ['description' => 'Acesso total', 'is_default' => true]
        );
        $permissionMap = $permissions->keyBy('key');
        $adminRole->permissions()->sync($permissions->pluck('id')->all());

        $secretaryRole = Role::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Secretaria'],
            ['description' => 'Agenda e atendimentos', 'is_default' => false]
        );

        $professionalRole = Role::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Profissional'],
            ['description' => 'Atendimentos e agenda', 'is_default' => false]
        );

        $secretaryPermissions = [
            'cadastro.pacientes.view',
            'cadastro.pacientes.create',
            'cadastro.pacientes.update',
            'agendamento.agenda.view',
            'agendamento.agendamentos.view',
            'agendamento.agendamentos.create',
            'agendamento.agendamentos.update',
            'agendamento.bloqueios.view',
            'agendamento.bloqueios.create',
            'agendamento.bloqueios.delete',
            'atendimento.agenda.view',
            'atendimento.atendimentos.view',
            'atendimento.atendimentos.create',
            'atendimento.atendimentos.update',
            'financeiro.contas_receber.view',
            'financeiro.contas_receber.create',
            'financeiro.contas_receber.update',
        ];
        $secretaryRole->permissions()->sync(
            collect($secretaryPermissions)
                ->map(fn ($key) => $permissionMap[$key]->id ?? null)
                ->filter()
                ->all()
        );

        $professionalPermissions = [
            'cadastro.pacientes.view',
            'agendamento.agenda.view',
            'agendamento.agendamentos.view',
            'agendamento.agendamentos.update',
            'atendimento.agenda.view',
            'atendimento.atendimentos.view',
            'atendimento.atendimentos.update',
        ];
        $professionalRole->permissions()->sync(
            collect($professionalPermissions)
                ->map(fn ($key) => $permissionMap[$key]->id ?? null)
                ->filter()
                ->all()
        );

        $company->users()->syncWithoutDetaching([
            $adminUser->id => ['role_id' => $adminRole->id, 'is_master' => true],
            $ownerUser->id => ['role_id' => $adminRole->id, 'is_master' => true],
            $professionalUser->id => ['role_id' => $professionalRole->id, 'is_master' => false],
        ]);

        $professional = Professional::updateOrCreate(
            ['user_id' => $professionalUser->id],
            [
                'company_id' => $company->id,
                'display_name' => 'Mariana Silva',
                'phone' => '+55 31 98888-0000',
                'email' => 'mariana@aqatende.local',
                'crm_number' => null,
                'crm_state' => null,
                'rqe' => null,
                'bio' => 'Profissional de beleza com atendimento em cabelo e estética.',
                'active' => true,
                'salary_type' => 'commission',
                'commission_type' => 'percentage',
                'commission_value' => 40,
            ]
        );

        $ownerProfessional = Professional::updateOrCreate(
            ['user_id' => $ownerUser->id],
            [
                'company_id' => $company->id,
                'display_name' => 'S Dronsh',
                'crm_number' => null,
                'crm_state' => null,
                'rqe' => null,
                'bio' => 'Profissional de teste para o sistema.',
                'active' => true,
                'salary_type' => 'commission',
                'commission_type' => 'percentage',
                'commission_value' => 30,
            ]
        );

        $professional->specialties()->sync($specialtyModels->pluck('id')->all());
        $professional->units()->sync([$unit->id]);
        $ownerProfessional->specialties()->sync($specialtyModels->pluck('id')->all());
        $ownerProfessional->units()->sync([$unit->id]);
        $unit->specialties()->sync($specialtyModels->pluck('id')->all());

        Patient::updateOrCreate(
            ['user_id' => $patientUser->id],
            [
                'full_name' => 'Cliente Demo',
                'cpf' => '000.000.000-00',
                'phone' => '+55 31 90000-0000',
            ]
        );

        Patient::updateOrCreate(
            ['user_id' => $ownerUser->id],
            [
                'full_name' => 'S Dronsh',
                'cpf' => '111.111.111-11',
                'phone' => '+55 31 90000-1111',
            ]
        );

        $haircut = Service::updateOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Corte de cabelo'],
            [
                'unit_id' => $unit->id,
                'description' => 'Corte feminino ou masculino.',
                'duration_minutes' => 40,
                'modality' => 'presencial',
                'price_cents' => 8000,
                'active' => true,
            ]
        );

        $manicure = Service::updateOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Manicure'],
            [
                'unit_id' => $unit->id,
                'description' => 'Serviço de manicure.',
                'duration_minutes' => 50,
                'modality' => 'presencial',
                'price_cents' => 5000,
                'active' => true,
            ]
        );

        $professional->services()->syncWithoutDetaching([
            $haircut->id => ['active' => true, 'commission_type' => 'percentage', 'commission_value' => 40],
            $manicure->id => ['active' => true, 'commission_type' => 'percentage', 'commission_value' => 35],
        ]);
        $ownerProfessional->services()->syncWithoutDetaching([
            $haircut->id => ['active' => true, 'commission_type' => 'percentage', 'commission_value' => 30],
        ]);

        $insurancePlans = [
            'Unimed',
            'Bradesco Saúde',
            'SulAmérica',
        ];

        $insurancePlanModels = collect($insurancePlans)->map(function (string $name) {
            return InsurancePlan::updateOrCreate(['name' => $name], ['active' => true]);
        });

        $professional->insurancePlans()->syncWithPivotValues(
            $insurancePlanModels->pluck('id')->all(),
            ['active' => true]
        );

        $weekdays = [1, 2, 3, 4, 5];

        foreach ($weekdays as $weekday) {
            Schedule::updateOrCreate(
                [
                    'professional_id' => $professional->id,
                    'unit_id' => $unit->id,
                    'weekday' => $weekday,
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                ],
                ['is_active' => true]
            );

            Schedule::updateOrCreate(
                [
                    'professional_id' => $professional->id,
                    'unit_id' => $unit->id,
                    'weekday' => $weekday,
                    'start_time' => '13:00:00',
                    'end_time' => '18:00:00',
                ],
                ['is_active' => true]
            );
        }

        // Sem bloqueios padrão; o período será controlado pelo horário do profissional.
    }

    protected function seedPermissions()
    {
        $modules = [
            'cadastro' => ['clinicas', 'unidades', 'profissionais', 'servicos', 'pacientes', 'especialidades'],
            'agendamento' => ['agenda', 'agendamentos', 'bloqueios'],
            'atendimento' => ['agenda', 'atendimentos'],
            'financeiro' => ['financeiro', 'contas_pagar', 'contas_receber', 'fluxo_caixa', 'categorias', 'contas_bancarias', 'relatorios'],
            'configuracoes' => ['logo'],
            'seguranca' => ['usuarios', 'perfis'],
        ];

        $actions = [
            'view' => 'Visualizar',
            'create' => 'Criar',
            'update' => 'Editar',
            'delete' => 'Excluir',
        ];

        $permissions = collect();

        foreach ($modules as $module => $resources) {
            foreach ($resources as $resource) {
                foreach ($actions as $action => $label) {
                    $key = "{$module}.{$resource}.{$action}";
                    $permissions->push(Permission::updateOrCreate(
                        ['key' => $key],
                        [
                            'module' => $module,
                            'resource' => $resource,
                            'action' => $action,
                            'label' => "{$label} {$resource}",
                        ]
                    ));
                }
            }
        }

        return $permissions;
    }

    private function applyDefaultLogo(Company $company): void
    {
        $logoPath = 'company_logos/aqatende-default.png';
        $sourcePath = public_path('logo.png');

        if (is_file($sourcePath) && ! Storage::disk('public')->exists($logoPath)) {
            Storage::disk('public')->put($logoPath, file_get_contents($sourcePath));
        }

        if (Storage::disk('public')->exists($logoPath)) {
            CompanySetting::updateOrCreate(
                ['company_id' => $company->id, 'key' => 'logo_path'],
                ['value' => $logoPath]
            );
        }
    }
}
