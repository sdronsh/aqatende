@php
    $contact = $clinic->contact ?? null;
    $certificate = $clinic->certificate ?? null;
    $taxProfile = $clinic->taxProfile ?? null;
    $health = $clinic->healthRegulation ?? null;
    $bank = $clinic->bankAccount ?? null;
    $responsibles = $clinic->responsibles ?? collect();
    $respTechnical = $responsibles->firstWhere('type', 'technical');
    $respLegal = $responsibles->firstWhere('type', 'legal');
    $contracts = $clinic->insuranceContracts ?? collect();
    $partners = $clinic->partners ?? collect();

    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10';
    $mirrorInput = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 shadow-theme-xs';
    $select = $input;
    $textarea = $input;
    $scheduleStart = old('schedule_start_time', $clinic->schedule_start_time ? substr($clinic->schedule_start_time, 0, 5) : '');
    $scheduleEnd = old('schedule_end_time', $clinic->schedule_end_time ? substr($clinic->schedule_end_time, 0, 5) : '');
    $company = $clinic->company ?? null;
    $terms = $terms ?? [];
    $termsVersion = $terms['version'] ?? '1.0';
    $termsEffectiveAt = $terms['effective_at'] ?? null;
    $termsBody = $terms['body'] ?? '';
    $termsAcceptedAt = $clinic->terms_accepted_at ?? null;
    $termsAcceptedVersion = $clinic->terms_version ?? null;
    $termsAcceptedIp = $clinic->terms_accepted_ip ?? null;
    $termsAcceptedUserId = $clinic->terms_accepted_user_id ?? null;
    $termsAccepted = $termsAcceptedAt && $termsAcceptedVersion === $termsVersion;
    $acceptedMeta = [];
    if ($termsAcceptedIp) {
        $acceptedMeta[] = "IP {$termsAcceptedIp}";
    }
    if ($termsAcceptedUserId) {
        $acceptedMeta[] = "Usuario #{$termsAcceptedUserId}";
    }
    $acceptedMetaText = $acceptedMeta ? ' · '.implode(' · ', $acceptedMeta) : '';
    $activeTab = request('tab', 'basic');
@endphp

<ul class="mb-4 flex flex-wrap gap-2 border-b border-gray-200 text-sm">
    <li>
        <button class="rounded-t-lg border border-b-0 border-gray-200 bg-white px-4 py-2 font-medium text-gray-700" type="button" data-tab="basic">Dados basicos</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="contact">Endereco e contato</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="cert">Certificacao digital</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="tax">Fiscal e tributario</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="health">Regulatorio</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="bank">Bancario</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="insurance">Convenios</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="partners">Quadro societario</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="resp">Responsaveis</button>
    </li>
    <li>
        <button class="rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500" type="button" data-tab="terms">Termo de uso</button>
    </li>
</ul>

<div class="rounded-xl border border-gray-200 p-4">
    <div data-tab-pane="basic" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="code">Codigo interno</label>
                <input class="{{ $mirrorInput }}" id="code" name="code" value="{{ old('code', $clinic->code ?? $company?->code ?? '') }}" readonly />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Nome</label>
                <input class="{{ $mirrorInput }}" id="name" name="name" value="{{ old('name', $clinic->name ?? '') }}" readonly />
                @error('name')<div class="text-error-500 text-sm mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_name">Razao social</label>
                <input class="{{ $mirrorInput }}" id="legal_name" name="legal_name" value="{{ old('legal_name', $clinic->legal_name ?? '') }}" readonly />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="trade_name">Nome fantasia</label>
                <input class="{{ $input }}" id="trade_name" name="trade_name" value="{{ old('trade_name', $clinic->trade_name ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="cnpj">CNPJ</label>
                <input class="{{ $mirrorInput }}" id="cnpj" name="cnpj" value="{{ old('cnpj', $clinic->cnpj ?? '') }}" readonly />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
                <input class="{{ $mirrorInput }}" id="email" name="email" value="{{ old('email', $clinic->email ?? '') }}" readonly />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="phone">Telefone</label>
                <input class="{{ $mirrorInput }}" id="phone" name="phone" value="{{ old('phone', $clinic->phone ?? '') }}" readonly />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="cnae_main">CNAE principal</label>
                <input class="{{ $input }}" id="cnae_main" name="cnae_main" value="{{ old('cnae_main', $clinic->cnae_main ?? '') }}" />
            </div>
            <div class="md:col-span-12">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="cnae_secondary">CNAEs secundarios</label>
                <textarea class="{{ $textarea }}" id="cnae_secondary" name="cnae_secondary" rows="2">{{ old('cnae_secondary', $clinic->cnae_secondary ?? '') }}</textarea>
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_nature">Natureza juridica</label>
                <input class="{{ $input }}" id="legal_nature" name="legal_nature" value="{{ old('legal_nature', $clinic->legal_nature ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="state_registration">Inscricao estadual</label>
                <input class="{{ $input }}" id="state_registration" name="state_registration" value="{{ old('state_registration', $clinic->state_registration ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="municipal_registration">Inscricao municipal</label>
                <input class="{{ $input }}" id="municipal_registration" name="municipal_registration" value="{{ old('municipal_registration', $clinic->municipal_registration ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_regime">Regime tributario</label>
                @php $taxRegime = old('tax_regime', $clinic->tax_regime ?? ''); @endphp
                <select class="{{ $select }}" id="tax_regime" name="tax_regime">
                    <option value="">Selecione</option>
                    <option value="SN" @selected($taxRegime === 'SN')>Simples Nacional</option>
                    <option value="LP" @selected($taxRegime === 'LP')>Lucro Presumido</option>
                    <option value="LR" @selected($taxRegime === 'LR')>Lucro Real</option>
                    <option value="IM" @selected($taxRegime === 'IM')>Imune/Isenta</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="schedule_start_time">Inicio atendimento</label>
                <input class="{{ $input }}" id="schedule_start_time" name="schedule_start_time" type="time" value="{{ $scheduleStart }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="schedule_end_time">Fim atendimento</label>
                <input class="{{ $input }}" id="schedule_end_time" name="schedule_end_time" type="time" value="{{ $scheduleEnd }}" />
            </div>
            <div class="md:col-span-4">
                @php $active = old('active', $clinic->active ?? true); @endphp
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 mt-6">
                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked($active) disabled />
                    Clinica ativa
                </label>
            </div>
        </div>
    </div>

    <div data-tab-pane="contact" class="hidden space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_zip">CEP</label>
                <input class="{{ $input }}" id="contact_zip" name="contact[zip]" value="{{ old('contact.zip', $contact->zip ?? '') }}" />
                <p id="contact_zip_status" class="mt-1 text-xs text-gray-500"></p>
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_address_line1">Endereco completo</label>
                <input class="{{ $input }}" id="contact_address_line1" name="contact[address_line1]" value="{{ old('contact.address_line1', $contact->address_line1 ?? '') }}" />
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_number">Numero</label>
                <input class="{{ $input }}" id="contact_number" name="contact[number]" value="{{ old('contact.number', $contact->number ?? '') }}" />
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_complement">Complemento</label>
                <input class="{{ $input }}" id="contact_complement" name="contact[complement]" value="{{ old('contact.complement', $contact->complement ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_district">Bairro</label>
                <input class="{{ $input }}" id="contact_district" name="contact[district]" value="{{ old('contact.district', $contact->district ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_city">Cidade</label>
                <input class="{{ $input }}" id="contact_city" name="contact[city]" value="{{ old('contact.city', $contact->city ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_state">Estado</label>
                <input class="{{ $input }}" id="contact_state" name="contact[state]" value="{{ old('contact.state', $contact->state ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_phone">Telefone fixo</label>
                <input class="{{ $input }}" id="contact_phone" name="contact[phone]" value="{{ old('contact.phone', $contact->phone ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_whatsapp">WhatsApp</label>
                <input class="{{ $input }}" id="contact_whatsapp" name="contact[whatsapp]" value="{{ old('contact.whatsapp', $contact->whatsapp ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_email">E-mail institucional</label>
                <input class="{{ $input }}" id="contact_email" name="contact[email]" value="{{ old('contact.email', $contact->email ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_website">Site</label>
                <input class="{{ $input }}" id="contact_website" name="contact[website]" value="{{ old('contact.website', $contact->website ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="contact_admin">Responsavel administrativo</label>
                <input class="{{ $input }}" id="contact_admin" name="contact[admin_responsible]" value="{{ old('contact.admin_responsible', $contact->admin_responsible ?? '') }}" />
            </div>
        </div>
    </div>

    <div data-tab-pane="cert" class="hidden space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="certificate_type">Tipo de certificado</label>
                @php $certType = old('certificate.certificate_type', $certificate->certificate_type ?? ''); @endphp
                <select class="{{ $select }}" id="certificate_type" name="certificate[certificate_type]">
                    <option value="">Selecione</option>
                    <option value="A1" @selected($certType === 'A1')>A1</option>
                    <option value="A3" @selected($certType === 'A3')>A3</option>
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="certificate_file">Arquivo .pfx ou token</label>
                <input class="{{ $input }}" id="certificate_file" name="certificate[file_path]" value="{{ old('certificate.file_path', $certificate->file_path ?? '') }}" />
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="certificate_password">Senha</label>
                <input class="{{ $input }}" id="certificate_password" name="certificate[password]" value="{{ old('certificate.password', $certificate->password ?? '') }}" />
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="certificate_valid_until">Validade</label>
                <input class="{{ $input }}" id="certificate_valid_until" type="date" name="certificate[valid_until]" value="{{ old('certificate.valid_until', optional($certificate)->valid_until?->format('Y-m-d')) }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="certificate_signer">Responsavel assinatura</label>
                <input class="{{ $input }}" id="certificate_signer" name="certificate[signer_name]" value="{{ old('certificate.signer_name', $certificate->signer_name ?? '') }}" />
            </div>
        </div>
    </div>

    <div data-tab-pane="tax" class="hidden space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_regime_detail">Regime tributario</label>
                @php $taxDetail = old('tax.tax_regime', $taxProfile->tax_regime ?? ''); @endphp
                <select class="{{ $select }}" id="tax_regime_detail" name="tax[tax_regime]">
                    <option value="">Selecione</option>
                    <option value="SN" @selected($taxDetail === 'SN')>Simples Nacional</option>
                    <option value="LP" @selected($taxDetail === 'LP')>Lucro Presumido</option>
                    <option value="LR" @selected($taxDetail === 'LR')>Lucro Real</option>
                    <option value="IM" @selected($taxDetail === 'IM')>Imune/Isenta</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_option_date">Data de opcao</label>
                <input class="{{ $input }}" type="date" id="tax_option_date" name="tax[option_date]" value="{{ old('tax.option_date', optional($taxProfile)->option_date?->format('Y-m-d')) }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_iss_rate">Aliquota ISS (%)</label>
                <input class="{{ $input }}" id="tax_iss_rate" name="tax[iss_rate]" value="{{ old('tax.iss_rate', $taxProfile->iss_rate ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_iss_withheld">ISS retido</label>
                @php $issWithheld = old('tax.iss_withheld', $taxProfile->iss_withheld ?? null); @endphp
                <select class="{{ $select }}" id="tax_iss_withheld" name="tax[iss_withheld]">
                    <option value="">Selecione</option>
                    <option value="1" @selected($issWithheld === true || $issWithheld === '1')>Sim</option>
                    <option value="0" @selected($issWithheld === false || $issWithheld === '0')>Nao</option>
                </select>
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_service_list">Lista de servicos LC 116</label>
                <input class="{{ $input }}" id="tax_service_list" name="tax[service_list_lc116]" value="{{ old('tax.service_list_lc116', $taxProfile->service_list_lc116 ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_service_code">Codigo de servico municipal</label>
                <input class="{{ $input }}" id="tax_service_code" name="tax[service_code_municipal]" value="{{ old('tax.service_code_municipal', $taxProfile->service_code_municipal ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_irrf">IRRF (%)</label>
                <input class="{{ $input }}" id="tax_irrf" name="tax[irrf_rate]" value="{{ old('tax.irrf_rate', $taxProfile->irrf_rate ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_pis">PIS/COFINS/CSLL (%)</label>
                <input class="{{ $input }}" id="tax_pis" name="tax[pis_cofins_csll_rate]" value="{{ old('tax.pis_cofins_csll_rate', $taxProfile->pis_cofins_csll_rate ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_inss">INSS (%)</label>
                <input class="{{ $input }}" id="tax_inss" name="tax[inss_rate]" value="{{ old('tax.inss_rate', $taxProfile->inss_rate ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_nfse_service">Codigo servico NFS-e</label>
                <input class="{{ $input }}" id="tax_nfse_service" name="tax[nfse_service_code]" value="{{ old('tax.nfse_service_code', $taxProfile->nfse_service_code ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_operation">Natureza operacao</label>
                <input class="{{ $input }}" id="tax_operation" name="tax[nfse_operation_nature]" value="{{ old('tax.nfse_operation_nature', $taxProfile->nfse_operation_nature ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_iss_type">Tributacao ISS</label>
                <input class="{{ $input }}" id="tax_iss_type" name="tax[iss_taxation_type]" value="{{ old('tax.iss_taxation_type', $taxProfile->iss_taxation_type ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_special">Regime especial</label>
                <input class="{{ $input }}" id="tax_special" name="tax[special_tax_regime]" value="{{ old('tax.special_tax_regime', $taxProfile->special_tax_regime ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_env">Ambiente</label>
                @php $env = old('tax.environment', $taxProfile->environment ?? ''); @endphp
                <select class="{{ $select }}" id="tax_env" name="tax[environment]">
                    <option value="">Selecione</option>
                    <option value="homologacao" @selected($env === 'homologacao')>Homologacao</option>
                    <option value="producao" @selected($env === 'producao')>Producao</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_token">Token/codigo prefeitura</label>
                <input class="{{ $input }}" id="tax_token" name="tax[city_token]" value="{{ old('tax.city_token', $taxProfile->city_token ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_series">Serie NFS-e</label>
                <input class="{{ $input }}" id="tax_series" name="tax[nfse_series]" value="{{ old('tax.nfse_series', $taxProfile->nfse_series ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_initial">Numero inicial</label>
                <input class="{{ $input }}" id="tax_initial" name="tax[nfse_initial_number]" value="{{ old('tax.nfse_initial_number', $taxProfile->nfse_initial_number ?? '') }}" />
            </div>
        </div>
    </div>

    <div data-tab-pane="health" class="hidden space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_anvisa">ANVISA</label>
                <input class="{{ $input }}" id="health_anvisa" name="health[anvisa]" value="{{ old('health.anvisa', $health->anvisa ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_cnes">CNES</label>
                <input class="{{ $input }}" id="health_cnes" name="health[cnes]" value="{{ old('health.cnes', $health->cnes ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_permit">Alvara sanitario</label>
                <input class="{{ $input }}" id="health_permit" name="health[sanitary_permit]" value="{{ old('health.sanitary_permit', $health->sanitary_permit ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_issued">Data emissao</label>
                <input class="{{ $input }}" type="date" id="health_issued" name="health[permit_issued_at]" value="{{ old('health.permit_issued_at', optional($health)->permit_issued_at?->format('Y-m-d')) }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_valid">Validade</label>
                <input class="{{ $input }}" type="date" id="health_valid" name="health[permit_valid_until]" value="{{ old('health.permit_valid_until', optional($health)->permit_valid_until?->format('Y-m-d')) }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_rt">Responsavel tecnico (RT)</label>
                <input class="{{ $input }}" id="health_rt" name="health[tech_responsible_name]" value="{{ old('health.tech_responsible_name', $health->tech_responsible_name ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_council">Conselho RT</label>
                <input class="{{ $input }}" id="health_council" name="health[tech_responsible_council]" value="{{ old('health.tech_responsible_council', $health->tech_responsible_council ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_council_number">Numero conselho</label>
                <input class="{{ $input }}" id="health_council_number" name="health[tech_responsible_number]" value="{{ old('health.tech_responsible_number', $health->tech_responsible_number ?? '') }}" />
            </div>
            <div class="md:col-span-8">
                <label class="mb-2 block text-sm font-medium text-gray-700">Especialidades atendidas</label>
                @php
                    $selectedHealthSpecialties = old('health.specialties', $health->specialties ?? []);
                    if (is_string($selectedHealthSpecialties)) {
                        $decoded = json_decode($selectedHealthSpecialties, true);
                        if (is_array($decoded)) {
                            $selectedHealthSpecialties = $decoded;
                        } else {
                            $selectedHealthSpecialties = array_filter(array_map('trim', explode(',', $selectedHealthSpecialties)));
                        }
                    }
                    $selectedHealthSpecialties = array_map('intval', $selectedHealthSpecialties ?? []);
                @endphp
                <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($specialties as $specialty)
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="health[specialties][]" value="{{ $specialty->id }}" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(in_array($specialty->id, $selectedHealthSpecialties, true)) />
                            {{ $specialty->name }}
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('health.specialties')" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_ans">Atende planos (ANS)</label>
                @php $ansEnabled = old('health.ans_enabled', $health->ans_enabled ?? null); @endphp
                <select class="{{ $select }}" id="health_ans" name="health[ans_enabled]">
                    <option value="">Selecione</option>
                    <option value="1" @selected($ansEnabled === true || $ansEnabled === '1')>Sim</option>
                    <option value="0" @selected($ansEnabled === false || $ansEnabled === '0')>Nao</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_ans_reg">Registro ANS</label>
                <input class="{{ $input }}" id="health_ans_reg" name="health[ans_registration]" value="{{ old('health.ans_registration', $health->ans_registration ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_accreditation">Tipo credenciamento</label>
                <input class="{{ $input }}" id="health_accreditation" name="health[accreditation_type]" value="{{ old('health.accreditation_type', $health->accreditation_type ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_tables">Tabelas utilizadas</label>
                <input class="{{ $input }}" id="health_tables" name="health[tables_used]" value="{{ old('health.tables_used', $health->tables_used ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="health_plans">Convenios atendidos</label>
                <textarea class="{{ $textarea }}" id="health_plans" name="health[insurance_plans]" rows="2">{{ old('health.insurance_plans', $health->insurance_plans ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div data-tab-pane="bank" class="hidden space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_name">Banco</label>
                <input class="{{ $input }}" id="bank_name" name="bank[bank_name]" value="{{ old('bank.bank_name', $bank->bank_name ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_agency">Agencia</label>
                <input class="{{ $input }}" id="bank_agency" name="bank[agency]" value="{{ old('bank.agency', $bank->agency ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_account">Conta</label>
                <input class="{{ $input }}" id="bank_account" name="bank[account]" value="{{ old('bank.account', $bank->account ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_account_type">Tipo de conta</label>
                <input class="{{ $input }}" id="bank_account_type" name="bank[account_type]" value="{{ old('bank.account_type', $bank->account_type ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_pix">PIX</label>
                <input class="{{ $input }}" id="bank_pix" name="bank[pix_key]" value="{{ old('bank.pix_key', $bank->pix_key ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_fin_resp">Responsavel financeiro</label>
                <input class="{{ $input }}" id="bank_fin_resp" name="bank[financial_responsible_name]" value="{{ old('bank.financial_responsible_name', $bank->financial_responsible_name ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_fin_cpf">CPF responsavel financeiro</label>
                <input class="{{ $input }}" id="bank_fin_cpf" name="bank[financial_responsible_cpf]" value="{{ old('bank.financial_responsible_cpf', $bank->financial_responsible_cpf ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_email">E-mail cobranca</label>
                <input class="{{ $input }}" id="bank_email" name="bank[billing_email]" value="{{ old('bank.billing_email', $bank->billing_email ?? '') }}" />
            </div>
            <div class="md:col-span-12">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bank_boleto">Configuracao boletos</label>
                <textarea class="{{ $textarea }}" id="bank_boleto" name="bank[boleto_config]" rows="2">{{ old('bank.boleto_config', $bank->boleto_config ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div data-tab-pane="insurance" class="hidden space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-gray-700">Convenios e operadoras</div>
            <button class="rounded-lg border border-brand-500 px-3 py-1 text-xs font-medium text-brand-500" type="button" id="add-insurance-row">Adicionar</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm" id="insurance-table">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-3 py-2">Convenio</th>
                        <th class="border border-gray-200 px-3 py-2">Credenciamento</th>
                        <th class="border border-gray-200 px-3 py-2">Contratacao</th>
                        <th class="border border-gray-200 px-3 py-2">Tabela</th>
                        <th class="border border-gray-200 px-3 py-2">Glosa %</th>
                        <th class="border border-gray-200 px-3 py-2">Envio</th>
                        <th class="border border-gray-200 px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $oldContracts = old('insurance_contracts');
                        $rows = $oldContracts !== null ? collect($oldContracts) : $contracts;
                    @endphp
                    @forelse ($rows as $index => $row)
                        @php
                            $rowData = is_array($row) ? $row : $row->toArray();
                        @endphp
                        <tr>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="insurance_contracts[{{ $index }}][plan_name]" value="{{ $rowData['plan_name'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="insurance_contracts[{{ $index }}][credential_code]" value="{{ $rowData['credential_code'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="insurance_contracts[{{ $index }}][contract_type]" value="{{ $rowData['contract_type'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="insurance_contracts[{{ $index }}][table_type]" value="{{ $rowData['table_type'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="insurance_contracts[{{ $index }}][glosa_percent]" value="{{ $rowData['glosa_percent'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="insurance_contracts[{{ $index }}][submission_type]" value="{{ $rowData['submission_type'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2 text-center">
                                <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 remove-insurance-row" type="button">Remover</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-200 px-4 py-4 text-center text-gray-500">Nenhum convenio cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div data-tab-pane="partners" class="hidden space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-gray-700">Quadro societario</div>
            <button class="rounded-lg border border-brand-500 px-3 py-1 text-xs font-medium text-brand-500" type="button" id="add-partner-row">Adicionar</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border border-gray-200 [border-spacing:0] text-sm" id="partner-table">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                        <th class="border border-gray-200 px-3 py-2">Socio</th>
                        <th class="border border-gray-200 px-3 py-2">CPF</th>
                        <th class="border border-gray-200 px-3 py-2">E-mail</th>
                        <th class="border border-gray-200 px-3 py-2">Telefone</th>
                        <th class="border border-gray-200 px-3 py-2">Funcao</th>
                        <th class="border border-gray-200 px-3 py-2">Participacao (%)</th>
                        <th class="border border-gray-200 px-3 py-2 text-center">Repasse</th>
                        <th class="border border-gray-200 px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $oldPartners = old('partners');
                        $partnerRows = $oldPartners !== null ? collect($oldPartners) : $partners;
                    @endphp
                    @forelse ($partnerRows as $index => $partner)
                        @php
                            $rowData = is_array($partner) ? $partner : $partner->toArray();
                        @endphp
                        @php $rowRepasse = $rowData['repasse'] ?? false; @endphp
                        <tr>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="partners[{{ $index }}][name]" value="{{ $rowData['name'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2">
                                <input class="{{ $input }}" name="partners[{{ $index }}][cpf]" value="{{ $rowData['cpf'] ?? '' }}" data-mask="cpf" />
                            </td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="partners[{{ $index }}][email]" value="{{ $rowData['email'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="partners[{{ $index }}][phone]" value="{{ $rowData['phone'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2"><input class="{{ $input }}" name="partners[{{ $index }}][role]" value="{{ $rowData['role'] ?? '' }}" /></td>
                            <td class="border border-gray-200 px-3 py-2">
                                <input class="{{ $input }}" name="partners[{{ $index }}][share_percent]" value="{{ $rowData['share_percent'] ?? '' }}" data-mask="percent" />
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center">
                                <input type="hidden" name="partners[{{ $index }}][repasse]" value="0" />
                                <input type="checkbox" name="partners[{{ $index }}][repasse]" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked((bool) $rowRepasse) />
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center">
                                <button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 remove-partner-row" type="button">Remover</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-gray-200 px-4 py-4 text-center text-gray-500">Nenhum socio cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="partner-share-warning" class="text-xs text-warning-700"></div>
    </div>

    <div data-tab-pane="resp" class="hidden space-y-4">
        <div class="grid gap-4 md:grid-cols-12">
            <div class="md:col-span-12 text-sm font-medium text-gray-700">Responsavel tecnico</div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_name">Nome</label>
                <input class="{{ $input }}" id="rt_name" name="responsible_technical[name]" value="{{ old('responsible_technical.name', $respTechnical->name ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_cpf">CPF</label>
                <input class="{{ $input }}" id="rt_cpf" name="responsible_technical[cpf]" value="{{ old('responsible_technical.cpf', $respTechnical->cpf ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_specialty">Especialidade</label>
                <input class="{{ $input }}" id="rt_specialty" name="responsible_technical[specialty]" value="{{ old('responsible_technical.specialty', $respTechnical->specialty ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_council">Conselho</label>
                <input class="{{ $input }}" id="rt_council" name="responsible_technical[council_type]" value="{{ old('responsible_technical.council_type', $respTechnical->council_type ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_council_number">Numero conselho</label>
                <input class="{{ $input }}" id="rt_council_number" name="responsible_technical[council_number]" value="{{ old('responsible_technical.council_number', $respTechnical->council_number ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_certificate">Certificado RT</label>
                <input class="{{ $input }}" id="rt_certificate" name="responsible_technical[certificate_path]" value="{{ old('responsible_technical.certificate_path', $respTechnical->certificate_path ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_email">Email</label>
                <input class="{{ $input }}" id="rt_email" name="responsible_technical[email]" value="{{ old('responsible_technical.email', $respTechnical->email ?? '') }}" />
            </div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="rt_phone">Telefone</label>
                <input class="{{ $input }}" id="rt_phone" name="responsible_technical[phone]" value="{{ old('responsible_technical.phone', $respTechnical->phone ?? '') }}" />
            </div>

            <div class="md:col-span-12 text-sm font-medium text-gray-700 mt-2">Responsavel legal</div>
            <div class="md:col-span-6">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_name">Nome</label>
                <input class="{{ $input }}" id="legal_name" name="responsible_legal[name]" value="{{ old('responsible_legal.name', $respLegal->name ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_cpf">CPF</label>
                <input class="{{ $input }}" id="legal_cpf" name="responsible_legal[cpf]" value="{{ old('responsible_legal.cpf', $respLegal->cpf ?? '') }}" />
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_email">Email</label>
                <input class="{{ $input }}" id="legal_email" name="responsible_legal[email]" value="{{ old('responsible_legal.email', $respLegal->email ?? '') }}" />
            </div>
            <div class="md:col-span-4">
                <label class="mb-1 block text-sm font-medium text-gray-700" for="legal_phone">Telefone</label>
                <input class="{{ $input }}" id="legal_phone" name="responsible_legal[phone]" value="{{ old('responsible_legal.phone', $respLegal->phone ?? '') }}" />
            </div>
        </div>
    </div>

    <div data-tab-pane="terms" class="hidden space-y-4">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="font-medium text-gray-800">Termo de Uso</div>
                <div class="text-xs text-gray-500">Versao {{ $termsVersion }}@if ($termsEffectiveAt) • Vigente desde {{ $termsEffectiveAt }}@endif</div>
            </div>
            <div class="mt-3 max-h-96 overflow-auto overflow-x-hidden">
                <pre class="m-0 max-w-full text-sm text-gray-700" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $termsBody }}</pre>
            </div>
        </div>

        @if ($termsAcceptedAt && $termsAcceptedVersion !== $termsVersion)
            <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">
                Uma nova versao do Termo de Uso esta disponivel. Aceite novamente para liberar agendamento e atendimento.
            </div>
        @elseif (! $termsAccepted)
            <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">
                Termo pendente. O agendamento e o atendimento ficam bloqueados ate o aceite.
            </div>
        @endif

        @if ($termsAccepted)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Termo aceito em {{ optional($termsAcceptedAt)->format('d/m/Y H:i') }}{{ $acceptedMetaText }}
            </div>
        @else
            <label class="inline-flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="terms_accept" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" @checked(old('terms_accept')) />
                <span>Li e aceito o Termo de Uso (versao {{ $termsVersion }}).</span>
            </label>
            @error('terms_accept')<div class="text-error-500 text-sm mt-1">{{ $message }}</div>@enderror
        @endif
    </div>
</div>

<script>
    const tabButtons = document.querySelectorAll('[data-tab]');
    const tabPanes = document.querySelectorAll('[data-tab-pane]');
    const activeTab = (tab) => {
        tabButtons.forEach((btn) => {
            const isActive = btn.dataset.tab === tab;
            btn.classList.toggle('bg-white', isActive);
            btn.classList.toggle('text-gray-700', isActive);
            btn.classList.toggle('bg-gray-50', !isActive);
            btn.classList.toggle('text-gray-500', !isActive);
        });
        tabPanes.forEach((pane) => {
            pane.classList.toggle('hidden', pane.dataset.tabPane !== tab);
        });
    };
    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => activeTab(btn.dataset.tab));
    });
    const initialTab = @json($activeTab);
    const availableTabs = new Set([...tabButtons].map((btn) => btn.dataset.tab));
    activeTab(availableTabs.has(initialTab) ? initialTab : 'basic');

    const insuranceTable = document.getElementById('insurance-table');
    const addInsurance = document.getElementById('add-insurance-row');
    const partnerTable = document.getElementById('partner-table');
    const addPartner = document.getElementById('add-partner-row');

    const addRow = () => {
        const tbody = insuranceTable.querySelector('tbody');
        const index = tbody.querySelectorAll('tr').length;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="insurance_contracts[${index}][plan_name]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="insurance_contracts[${index}][credential_code]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="insurance_contracts[${index}][contract_type]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="insurance_contracts[${index}][table_type]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="insurance_contracts[${index}][glosa_percent]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="insurance_contracts[${index}][submission_type]" /></td>
            <td class="border border-gray-200 px-3 py-2 text-center"><button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 remove-insurance-row" type="button">Remover</button></td>
        `;
        tbody.appendChild(row);
    };

    addInsurance?.addEventListener('click', () => {
        const emptyRow = insuranceTable.querySelector('tbody tr td[colspan]');
        if (emptyRow) {
            emptyRow.closest('tr').remove();
        }
        addRow();
    });

    insuranceTable?.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-insurance-row')) {
            event.target.closest('tr').remove();
        }
    });

    const addPartnerRow = () => {
        const tbody = partnerTable.querySelector('tbody');
        const index = tbody.querySelectorAll('tr').length;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="partners[${index}][name]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="partners[${index}][cpf]" data-mask="cpf" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="partners[${index}][email]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="partners[${index}][phone]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="partners[${index}][role]" /></td>
            <td class="border border-gray-200 px-3 py-2"><input class="${@json($input)}" name="partners[${index}][share_percent]" data-mask="percent" /></td>
            <td class="border border-gray-200 px-3 py-2 text-center">
                <input type="hidden" name="partners[${index}][repasse]" value="0" />
                <input type="checkbox" name="partners[${index}][repasse]" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
            </td>
            <td class="border border-gray-200 px-3 py-2 text-center"><button class="rounded-lg border border-error-500 px-2 py-1 text-xs font-medium text-error-500 remove-partner-row" type="button">Remover</button></td>
        `;
        tbody.appendChild(row);
    };

    addPartner?.addEventListener('click', () => {
        const emptyRow = partnerTable.querySelector('tbody tr td[colspan]');
        if (emptyRow) {
            emptyRow.closest('tr').remove();
        }
        addPartnerRow();
    });

    partnerTable?.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-partner-row')) {
            event.target.closest('tr').remove();
        }
    });

    const zipInput = document.getElementById('contact_zip');
    const zipStatus = document.getElementById('contact_zip_status');
    const addressLine1Input = document.getElementById('contact_address_line1');
    const districtInput = document.getElementById('contact_district');
    const cityInput = document.getElementById('contact_city');
    const stateInput = document.getElementById('contact_state');

    const setZipStatus = (message, isError = false) => {
        if (!zipStatus) return;
        zipStatus.textContent = message;
        zipStatus.classList.toggle('text-error-500', isError);
        zipStatus.classList.toggle('text-gray-500', !isError);
    };

    const fillIfEmpty = (input, value) => {
        if (!input || !value) return;
        if (input.value && input.value.trim() !== '') return;
        input.value = value;
    };

    const lookupCep = async () => {
        if (!zipInput) return;
        const cep = zipInput.value.replace(/\D/g, '');
        if (!cep) {
            setZipStatus('');
            return;
        }
        if (cep.length !== 8) {
            setZipStatus('CEP invalido. Use 8 digitos.', true);
            return;
        }

        setZipStatus('Buscando endereco...');
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error('CEP');
            }
            const data = await response.json();
            if (data.erro) {
                setZipStatus('CEP nao encontrado.', true);
                return;
            }

            fillIfEmpty(addressLine1Input, data.logradouro);
            fillIfEmpty(districtInput, data.bairro);
            fillIfEmpty(cityInput, data.localidade);
            fillIfEmpty(stateInput, data.uf);
            setZipStatus('Endereco preenchido.');
        } catch (error) {
            setZipStatus('Nao foi possivel buscar o CEP.', true);
        }
    };

    zipInput?.addEventListener('blur', lookupCep);
    zipInput?.addEventListener('input', () => setZipStatus(''));

    const formatCpf = (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 11);
        const parts = [];
        if (digits.length > 0) parts.push(digits.slice(0, 3));
        if (digits.length >= 4) parts.push(digits.slice(3, 6));
        if (digits.length >= 7) parts.push(digits.slice(6, 9));
        let formatted = parts.join('.');
        if (digits.length >= 10) {
            formatted += '-' + digits.slice(9, 11);
        }
        return formatted;
    };

    const normalizePercent = (value) => {
        const cleaned = value.replace(',', '.').replace(/[^0-9.]/g, '');
        const parts = cleaned.split('.');
        const integer = parts[0] ?? '';
        const decimal = (parts[1] ?? '').slice(0, 2);
        const normalized = decimal ? `${integer}.${decimal}` : integer;
        return normalized;
    };

    const applyMask = (input) => {
        if (!input) return;
        const type = input.dataset.mask;
        if (type === 'cpf') {
            input.value = formatCpf(input.value);
        }
        if (type === 'percent') {
            input.value = normalizePercent(input.value);
        }
    };

    const updateShareWarning = () => {
        if (!partnerTable) return;
        const inputs = partnerTable.querySelectorAll('input[name*="[share_percent]"]');
        let sum = 0;
        inputs.forEach((input) => {
            const value = parseFloat((input.value || '').replace(',', '.'));
            if (!Number.isNaN(value)) {
                sum += value;
            }
        });
        const warning = document.getElementById('partner-share-warning');
        if (!warning) return;
        if (inputs.length && sum !== 100) {
            warning.textContent = `A soma das participacoes esta em ${sum.toFixed(2)}%. O ideal e 100%.`;
        } else {
            warning.textContent = '';
        }
    };

    document.addEventListener('input', (event) => {
        const target = event.target;
        if (!target || !target.dataset?.mask) return;
        applyMask(target);
        if (target.dataset.mask === 'percent') {
            updateShareWarning();
        }
    });

    updateShareWarning();
</script>
