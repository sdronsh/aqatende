<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('address_line1')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->string('zip', 12)->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('admin_responsible')->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
        });

        Schema::create('clinic_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_type', 10)->nullable();
            $table->string('file_path')->nullable();
            $table->string('password')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('signer_name')->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
        });

        Schema::create('clinic_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('tax_regime', 30)->nullable();
            $table->date('option_date')->nullable();
            $table->decimal('iss_rate', 5, 2)->nullable();
            $table->string('service_list_lc116')->nullable();
            $table->string('service_code_municipal')->nullable();
            $table->boolean('iss_withheld')->nullable();
            $table->decimal('irrf_rate', 5, 2)->nullable();
            $table->decimal('pis_cofins_csll_rate', 5, 2)->nullable();
            $table->decimal('inss_rate', 5, 2)->nullable();
            $table->string('nfse_service_code')->nullable();
            $table->string('nfse_operation_nature')->nullable();
            $table->string('iss_taxation_type')->nullable();
            $table->string('special_tax_regime')->nullable();
            $table->string('environment')->nullable();
            $table->string('city_token')->nullable();
            $table->string('nfse_series')->nullable();
            $table->unsignedInteger('nfse_initial_number')->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
        });

        Schema::create('clinic_health_regulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('anvisa')->nullable();
            $table->string('cnes')->nullable();
            $table->string('sanitary_permit')->nullable();
            $table->date('permit_issued_at')->nullable();
            $table->date('permit_valid_until')->nullable();
            $table->string('tech_responsible_name')->nullable();
            $table->string('tech_responsible_council')->nullable();
            $table->string('tech_responsible_number')->nullable();
            $table->text('specialties')->nullable();
            $table->boolean('ans_enabled')->nullable();
            $table->string('ans_registration')->nullable();
            $table->string('accreditation_type')->nullable();
            $table->string('tables_used')->nullable();
            $table->text('insurance_plans')->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
        });

        Schema::create('clinic_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('account_type')->nullable();
            $table->string('pix_key')->nullable();
            $table->string('financial_responsible_name')->nullable();
            $table->string('financial_responsible_cpf', 20)->nullable();
            $table->string('billing_email')->nullable();
            $table->text('boleto_config')->nullable();
            $table->timestamps();

            $table->unique('clinic_id');
        });

        Schema::create('clinic_insurance_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name');
            $table->string('credential_code')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->string('table_type')->nullable();
            $table->decimal('glosa_percent', 5, 2)->nullable();
            $table->string('submission_type')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_responsibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name')->nullable();
            $table->string('cpf', 20)->nullable();
            $table->string('council_type', 20)->nullable();
            $table->string('council_number', 30)->nullable();
            $table->string('specialty')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_responsibles');
        Schema::dropIfExists('clinic_insurance_contracts');
        Schema::dropIfExists('clinic_bank_accounts');
        Schema::dropIfExists('clinic_health_regulations');
        Schema::dropIfExists('clinic_tax_profiles');
        Schema::dropIfExists('clinic_certificates');
        Schema::dropIfExists('clinic_contacts');
    }
};
