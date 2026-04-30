<?php

use App\Http\Controllers\CompanyLookupController;
use App\Http\Controllers\ClinicWebController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanySelectController;
use App\Http\Controllers\Admin\MasterUserController;
use App\Http\Controllers\AgendaWebController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceWebController;
use App\Http\Controllers\AppointmentWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfessionalWebController;
use App\Http\Controllers\QueueWebController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceWebController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpecialtyWebController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\Security\CompanyUserController;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\UnitWebController;
use App\Http\Controllers\PatientWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('home');
});

Route::get('/company-lookup', CompanyLookupController::class)
    ->name('company.lookup');
Route::get('/contratar/{plan}', [SubscriptionController::class, 'create'])
    ->name('subscriptions.create');
Route::post('/contratar/{plan}', [SubscriptionController::class, 'store'])
    ->name('subscriptions.store');
Route::get('/contratar/{plan}/assinatura', [SubscriptionController::class, 'billing'])
    ->name('subscriptions.billing');
Route::post('/contratar/{plan}/assinatura', [SubscriptionController::class, 'storeBilling'])
    ->name('subscriptions.billing.store');
Route::get('/contratar/{plan}/usuario', [SubscriptionController::class, 'adminUser'])
    ->name('subscriptions.admin');
Route::post('/contratar/{plan}/usuario', [SubscriptionController::class, 'storeAdminUser'])
    ->name('subscriptions.admin.store');
Route::get('/buscar', SearchController::class)->name('search');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'terms-accepted'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/support/request', SupportRequestController::class)->name('support.request');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cadastro/clinicas', [ClinicWebController::class, 'index'])->name('clinics.index');
    Route::get('/cadastro/clinicas/nova', [ClinicWebController::class, 'create'])->name('clinics.create');
    Route::post('/cadastro/clinicas', [ClinicWebController::class, 'store'])->name('clinics.store');
    Route::get('/cadastro/clinicas/{clinic}/edit', [ClinicWebController::class, 'edit'])->name('clinics.edit');
    Route::put('/cadastro/clinicas/{clinic}', [ClinicWebController::class, 'update'])->name('clinics.update');
    Route::delete('/cadastro/clinicas/{clinic}', [ClinicWebController::class, 'destroy'])->name('clinics.destroy');
    Route::get('/cadastro/unidades', [UnitWebController::class, 'index'])
        ->middleware('permission:cadastro.unidades.view')
        ->name('units.index');
    Route::get('/cadastro/unidades/nova', [UnitWebController::class, 'create'])
        ->middleware('permission:cadastro.unidades.create')
        ->name('units.create');
    Route::post('/cadastro/unidades', [UnitWebController::class, 'store'])
        ->middleware('permission:cadastro.unidades.create')
        ->name('units.store');
    Route::get('/cadastro/unidades/{unit}/edit', [UnitWebController::class, 'edit'])
        ->middleware('permission:cadastro.unidades.update')
        ->name('units.edit');
    Route::put('/cadastro/unidades/{unit}', [UnitWebController::class, 'update'])
        ->middleware('permission:cadastro.unidades.update')
        ->name('units.update');
    Route::delete('/cadastro/unidades/{unit}', [UnitWebController::class, 'destroy'])
        ->middleware('permission:cadastro.unidades.delete')
        ->name('units.destroy');
    Route::get('/cadastro/profissionais', [ProfessionalWebController::class, 'index'])
        ->middleware('permission:cadastro.profissionais.view')
        ->name('professionals.index');
    Route::get('/cadastro/profissionais/novo', [ProfessionalWebController::class, 'create'])
        ->middleware('permission:cadastro.profissionais.create')
        ->name('professionals.create');
    Route::post('/cadastro/profissionais', [ProfessionalWebController::class, 'store'])
        ->middleware('permission:cadastro.profissionais.create')
        ->name('professionals.store');
    Route::get('/cadastro/profissionais/{professional}/edit', [ProfessionalWebController::class, 'edit'])
        ->middleware('permission:cadastro.profissionais.update')
        ->name('professionals.edit');
    Route::put('/cadastro/profissionais/{professional}', [ProfessionalWebController::class, 'update'])
        ->middleware('permission:cadastro.profissionais.update')
        ->name('professionals.update');
    Route::delete('/cadastro/profissionais/{professional}', [ProfessionalWebController::class, 'destroy'])
        ->middleware('permission:cadastro.profissionais.delete')
        ->name('professionals.destroy');
    Route::get('/cadastro/servicos', [ServiceWebController::class, 'index'])
        ->middleware('permission:cadastro.servicos.view')
        ->name('services.index');
    Route::get('/cadastro/servicos/novo', [ServiceWebController::class, 'create'])
        ->middleware('permission:cadastro.servicos.create')
        ->name('services.create');
    Route::post('/cadastro/servicos', [ServiceWebController::class, 'store'])
        ->middleware('permission:cadastro.servicos.create')
        ->name('services.store');
    Route::get('/cadastro/servicos/{service}/edit', [ServiceWebController::class, 'edit'])
        ->middleware('permission:cadastro.servicos.update')
        ->name('services.edit');
    Route::put('/cadastro/servicos/{service}', [ServiceWebController::class, 'update'])
        ->middleware('permission:cadastro.servicos.update')
        ->name('services.update');
    Route::delete('/cadastro/servicos/{service}', [ServiceWebController::class, 'destroy'])
        ->middleware('permission:cadastro.servicos.delete')
        ->name('services.destroy');
    Route::get('/cadastro/pacientes', [PatientWebController::class, 'index'])
        ->middleware('permission:cadastro.pacientes.view')
        ->name('patients.index');
    Route::get('/cadastro/pacientes/novo', [PatientWebController::class, 'create'])
        ->middleware('permission:cadastro.pacientes.create')
        ->name('patients.create');
    Route::post('/cadastro/pacientes', [PatientWebController::class, 'store'])
        ->middleware('permission:cadastro.pacientes.create')
        ->name('patients.store');
    Route::get('/cadastro/pacientes/{patient}/edit', [PatientWebController::class, 'edit'])
        ->middleware('permission:cadastro.pacientes.update')
        ->name('patients.edit');
    Route::put('/cadastro/pacientes/{patient}', [PatientWebController::class, 'update'])
        ->middleware('permission:cadastro.pacientes.update')
        ->name('patients.update');
    Route::delete('/cadastro/pacientes/{patient}', [PatientWebController::class, 'destroy'])
        ->middleware('permission:cadastro.pacientes.delete')
        ->name('patients.destroy');

    Route::get('/agendamento/agenda', [AgendaWebController::class, 'index'])
        ->middleware(['permission:agendamento.agenda.view', 'terms-accepted'])
        ->name('agenda.index');
    Route::post('/agendamento/agenda', [AgendaWebController::class, 'store'])
        ->middleware(['permission:agendamento.agendamentos.create', 'terms-accepted'])
        ->name('agenda.store');
    Route::get('/agendamento/agendamentos', [AppointmentWebController::class, 'index'])
        ->middleware(['permission:agendamento.agendamentos.view', 'terms-accepted'])
        ->name('appointments.index');
    Route::get('/agendamento/agendamentos/novo', [AppointmentWebController::class, 'create'])
        ->middleware(['permission:agendamento.agendamentos.create', 'terms-accepted'])
        ->name('appointments.create');
    Route::post('/agendamento/agendamentos', [AppointmentWebController::class, 'store'])
        ->middleware(['permission:agendamento.agendamentos.create', 'terms-accepted'])
        ->name('appointments.store');
    Route::get('/agendamento/agendamentos/{appointment}/edit', [AppointmentWebController::class, 'edit'])
        ->middleware(['permission:agendamento.agendamentos.update', 'terms-accepted'])
        ->name('appointments.edit');
    Route::put('/agendamento/agendamentos/{appointment}', [AppointmentWebController::class, 'update'])
        ->middleware(['permission:agendamento.agendamentos.update', 'terms-accepted'])
        ->name('appointments.update');
    Route::delete('/agendamento/agendamentos/{appointment}', [AppointmentWebController::class, 'destroy'])
        ->middleware(['permission:agendamento.agendamentos.delete', 'terms-accepted'])
        ->name('appointments.destroy');

    Route::get('/atendimento/agenda', [AttendanceWebController::class, 'agenda'])
        ->middleware(['permission:atendimento.agenda.view', 'terms-accepted'])
        ->name('attendance.agenda');
    Route::get('/atendimento/fila', [QueueWebController::class, 'index'])
        ->middleware(['permission:atendimento.atendimentos.view', 'terms-accepted'])
        ->name('queue.index');
    Route::post('/atendimento/fila', [QueueWebController::class, 'store'])
        ->middleware(['permission:atendimento.atendimentos.create', 'terms-accepted'])
        ->name('queue.store');
    Route::post('/atendimento/fila/{appointment}/iniciar', [QueueWebController::class, 'start'])
        ->middleware(['permission:atendimento.atendimentos.update', 'terms-accepted'])
        ->name('queue.start');
    Route::post('/atendimento/fila/{appointment}/finalizar', [QueueWebController::class, 'finish'])
        ->middleware(['permission:atendimento.atendimentos.update', 'terms-accepted'])
        ->name('queue.finish');
    Route::get('/atendimento/lista', [AttendanceWebController::class, 'index'])
        ->middleware(['permission:atendimento.atendimentos.view', 'terms-accepted'])
        ->name('attendance.index');
    Route::get('/atendimento/{appointment}', [AttendanceRecordController::class, 'edit'])
        ->middleware(['permission:atendimento.atendimentos.view', 'terms-accepted'])
        ->name('attendance.record.edit');
    Route::put('/atendimento/{appointment}', [AttendanceRecordController::class, 'update'])
        ->middleware(['permission:atendimento.atendimentos.update', 'terms-accepted'])
        ->name('attendance.record.update');
    Route::post('/atendimento/{appointment}/reabrir', [AttendanceRecordController::class, 'reopen'])
        ->middleware(['permission:atendimento.atendimentos.update', 'terms-accepted'])
        ->name('attendance.record.reopen');

    Route::prefix('financeiro')->name('finance.')->group(function () {
        Route::get('/categorias', [\App\Http\Controllers\FinanceCategoryController::class, 'index'])
            ->name('categories.index');
        Route::get('/categorias/novo', [\App\Http\Controllers\FinanceCategoryController::class, 'create'])
            ->name('categories.create');
        Route::post('/categorias', [\App\Http\Controllers\FinanceCategoryController::class, 'store'])
            ->name('categories.store');
        Route::get('/categorias/{category}/editar', [\App\Http\Controllers\FinanceCategoryController::class, 'edit'])
            ->name('categories.edit');
        Route::put('/categorias/{category}', [\App\Http\Controllers\FinanceCategoryController::class, 'update'])
            ->name('categories.update');

        Route::get('/contas-bancarias', [\App\Http\Controllers\FinanceAccountController::class, 'index'])
            ->name('accounts.index');
        Route::get('/contas-bancarias/novo', [\App\Http\Controllers\FinanceAccountController::class, 'create'])
            ->name('accounts.create');
        Route::post('/contas-bancarias', [\App\Http\Controllers\FinanceAccountController::class, 'store'])
            ->name('accounts.store');
        Route::get('/contas-bancarias/{account}/editar', [\App\Http\Controllers\FinanceAccountController::class, 'edit'])
            ->name('accounts.edit');
        Route::put('/contas-bancarias/{account}', [\App\Http\Controllers\FinanceAccountController::class, 'update'])
            ->name('accounts.update');

        Route::get('/contas-receber', [\App\Http\Controllers\FinanceReceivableController::class, 'index'])
            ->name('receivables.index');
        Route::get('/contas-receber/novo', [\App\Http\Controllers\FinanceReceivableController::class, 'create'])
            ->name('receivables.create');
        Route::post('/contas-receber', [\App\Http\Controllers\FinanceReceivableController::class, 'store'])
            ->name('receivables.store');
        Route::get('/contas-receber/{receivable}/editar', [\App\Http\Controllers\FinanceReceivableController::class, 'edit'])
            ->name('receivables.edit');
        Route::put('/contas-receber/{receivable}', [\App\Http\Controllers\FinanceReceivableController::class, 'update'])
            ->name('receivables.update');

        Route::get('/contas-pagar', [\App\Http\Controllers\FinancePayableController::class, 'index'])
            ->name('payables.index');
        Route::get('/contas-pagar/novo', [\App\Http\Controllers\FinancePayableController::class, 'create'])
            ->name('payables.create');
        Route::post('/contas-pagar', [\App\Http\Controllers\FinancePayableController::class, 'store'])
            ->name('payables.store');
        Route::get('/contas-pagar/{payable}/editar', [\App\Http\Controllers\FinancePayableController::class, 'edit'])
            ->name('payables.edit');
        Route::put('/contas-pagar/{payable}', [\App\Http\Controllers\FinancePayableController::class, 'update'])
            ->name('payables.update');

        Route::get('/fluxo-caixa', [\App\Http\Controllers\FinanceCashflowController::class, 'index'])
            ->name('cashflow.index');
    });
    Route::get('/financeiro/relatorios', [\App\Http\Controllers\ReportsController::class, 'index'])
        ->middleware('permission:financeiro.relatorios.view')
        ->name('finance.reports');
    Route::get('/financeiro/relatorios/{report}', [\App\Http\Controllers\ReportsController::class, 'show'])
        ->middleware('permission:financeiro.relatorios.view')
        ->name('finance.reports.show');

    Route::prefix('configuracoes')->name('settings.')->group(function () {
        Route::get('/licenca', [\App\Http\Controllers\SettingsController::class, 'license'])
            ->middleware('permission:configuracoes.logo.view')
            ->name('license');
        Route::post('/licenca/pagamento', [\App\Http\Controllers\SettingsController::class, 'generateLicensePayment'])
            ->middleware('permission:configuracoes.logo.view')
            ->name('license.payment');

        Route::get('/logo', [\App\Http\Controllers\SettingsController::class, 'logo'])
            ->middleware('permission:configuracoes.logo.view')
            ->name('logo');
        Route::put('/logo', [\App\Http\Controllers\SettingsController::class, 'updateLogo'])
            ->middleware('permission:configuracoes.logo.update')
            ->name('logo.update');

        Route::get('/termo-uso', [\App\Http\Controllers\SettingsController::class, 'terms'])
            ->middleware('platform')
            ->name('terms.edit');
        Route::put('/termo-uso', [\App\Http\Controllers\SettingsController::class, 'updateTerms'])
            ->middleware('platform')
            ->name('terms.update');

    });

    Route::prefix('seguranca')->name('security.')->group(function () {
        Route::get('/usuarios-empresas', [CompanyUserController::class, 'companyMatrix'])
            ->middleware('permission:seguranca.usuarios.view')
            ->name('users.matrix');
        Route::get('/usuarios', [CompanyUserController::class, 'index'])
            ->middleware('permission:seguranca.usuarios.view')
            ->name('users.index');
        Route::get('/usuarios/novo', [CompanyUserController::class, 'create'])
            ->middleware('permission:seguranca.usuarios.create')
            ->name('users.create');
        Route::post('/usuarios', [CompanyUserController::class, 'store'])
            ->middleware('permission:seguranca.usuarios.create')
            ->name('users.store');
        Route::get('/usuarios/{user}/edit', [CompanyUserController::class, 'edit'])
            ->middleware('permission:seguranca.usuarios.update')
            ->name('users.edit');
        Route::put('/usuarios/{user}', [CompanyUserController::class, 'update'])
            ->middleware('permission:seguranca.usuarios.update')
            ->name('users.update');
        Route::delete('/usuarios/{user}', [CompanyUserController::class, 'destroy'])
            ->middleware('permission:seguranca.usuarios.delete')
            ->name('users.destroy');

        Route::get('/perfis', [RoleController::class, 'index'])
            ->middleware('permission:seguranca.perfis.view')
            ->name('roles.index');
        Route::get('/perfis/novo', [RoleController::class, 'create'])
            ->middleware('permission:seguranca.perfis.create')
            ->name('roles.create');
        Route::post('/perfis', [RoleController::class, 'store'])
            ->middleware('permission:seguranca.perfis.create')
            ->name('roles.store');
        Route::get('/perfis/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('permission:seguranca.perfis.update')
            ->name('roles.edit');
        Route::put('/perfis/{role}', [RoleController::class, 'update'])
            ->middleware('permission:seguranca.perfis.update')
            ->name('roles.update');
        Route::delete('/perfis/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:seguranca.perfis.delete')
            ->name('roles.destroy');
    });

    Route::prefix('administrativo')->name('admin.')->middleware('platform')->group(function () {
        Route::get('/selecionar-empresa', [CompanySelectController::class, 'index'])->name('company-select');
        Route::post('/selecionar-empresa', [CompanySelectController::class, 'store'])->name('company-select.store');
        Route::get('/empresas', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/empresas/nova', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/empresas', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/empresas/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/empresas/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('/empresas/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('/empresas/{company}/usuarios', [CompanyController::class, 'storeUser'])->name('companies.users.store');
        Route::delete('/empresas/{company}/usuarios/{user}', [CompanyController::class, 'destroyUser'])->name('companies.users.destroy');

        Route::get('/masters', [MasterUserController::class, 'index'])->name('masters.index');
        Route::get('/masters/novo', [MasterUserController::class, 'create'])->name('masters.create');
        Route::post('/masters', [MasterUserController::class, 'store'])->name('masters.store');
        Route::get('/masters/{user}/edit', [MasterUserController::class, 'edit'])->name('masters.edit');
        Route::put('/masters/{user}', [MasterUserController::class, 'update'])->name('masters.update');
        Route::delete('/masters/{user}', [MasterUserController::class, 'destroy'])->name('masters.destroy');
    });
});

require __DIR__.'/auth.php';
    Route::get('/cadastro/especialidades', [SpecialtyWebController::class, 'index'])
        ->middleware('permission:cadastro.especialidades.view')
        ->name('specialties.index');
    Route::get('/cadastro/especialidades/nova', [SpecialtyWebController::class, 'create'])
        ->middleware('permission:cadastro.especialidades.create')
        ->name('specialties.create');
    Route::post('/cadastro/especialidades', [SpecialtyWebController::class, 'store'])
        ->middleware('permission:cadastro.especialidades.create')
        ->name('specialties.store');
    Route::get('/cadastro/especialidades/{specialty}/edit', [SpecialtyWebController::class, 'edit'])
        ->middleware('permission:cadastro.especialidades.update')
        ->name('specialties.edit');
    Route::put('/cadastro/especialidades/{specialty}', [SpecialtyWebController::class, 'update'])
        ->middleware('permission:cadastro.especialidades.update')
        ->name('specialties.update');
    Route::delete('/cadastro/especialidades/{specialty}', [SpecialtyWebController::class, 'destroy'])
        ->middleware('permission:cadastro.especialidades.delete')
        ->name('specialties.destroy');
