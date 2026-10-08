@extends('admin.layouts.app')

@section('title', __('taxigo.split'))

@section('content')
<div class="go-page-head">
  <div>
    <div class="go-eyebrow mb-1">{{ __('taxigo.menu') }}</div>
    <h1 class="go-page-title">{{ __('taxigo.split') }}</h1>
    <p class="go-page-sub" style="max-width:80ch">{{ __('taxigo.split_sub') }}</p>
  </div>
</div>

@include('admin.taxigo._flash')

<form method="POST" action="{{ route('admin.taxigo.split.update') }}" id="split-form">
  @csrf @method('PUT')
  <div class="card mb-4">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="min-width:220px">{{ __('taxigo.beneficiary') }}</th>
            <th style="width:130px">{{ __('taxigo.share') }}</th>
            <th style="width:170px">{{ __('taxigo.channel') }}</th>
            <th style="min-width:380px">{{ __('taxigo.account') }}</th>
            <th class="text-right" style="width:120px">{{ __('taxigo.split_example') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($beneficiaries as $b)
            @php
              $row = fn ($field) => old("rows.{$b->id}.{$field}", $b->{$field});
              $ready = $b->is_driver_share || ($b->channel === 'bank' ? filled($b->bank_account_number) : filled($b->msisdn));
            @endphp
            <tr data-row>
              <td>
                <input type="text" name="rows[{{ $b->id }}][name]" value="{{ $row('name') }}" class="form-control form-control-sm fw-semibold mb-1" required maxlength="150" aria-label="{{ __('taxigo.beneficiary') }}">
                <span class="split-chip {{ $ready ? 'is-bank' : 'is-missing' }}">{{ $ready ? __('taxigo.ready') : __('taxigo.missing_account') }}</span>
              </td>
              <td>
                <div class="input-suffix is-pct">
                  <input type="number" step="0.01" min="0" max="100" name="rows[{{ $b->id }}][percent]" value="{{ rtrim(rtrim(number_format((float) $row('percent'), 2, '.', ''), '0'), '.') }}" class="form-control form-control-sm fw-bold" data-percent required aria-label="{{ __('taxigo.share') }} {{ $b->name }}">
                  <span>%</span>
                </div>
              </td>
              <td>
                @if ($b->is_driver_share)
                  <span class="small text-muted">{{ __('taxigo.driver') }}</span>
                @else
                  <select name="rows[{{ $b->id }}][channel]" class="form-select form-select-sm" data-channel aria-label="{{ __('taxigo.channel') }} {{ $b->name }}">
                    @foreach (__('taxigo.channels') as $value => $label)
                      <option value="{{ $value }}" @selected($row('channel') === $value)>{{ $label }}</option>
                    @endforeach
                  </select>
                @endif
              </td>
              <td>
                @if ($b->is_driver_share)
                  <span class="small text-muted">{{ __('taxigo.driver_share_note') }}</span>
                @else
                  <div data-mobile @class(['d-none' => $row('channel') === 'bank'])>
                    <input type="tel" name="rows[{{ $b->id }}][msisdn]" value="{{ $row('msisdn') }}" class="form-control form-control-sm" placeholder="{{ __('taxigo.mobile_number') }} · 6XX XX XX XX" aria-label="{{ __('taxigo.mobile_number') }} {{ $b->name }}">
                  </div>
                  <div data-bank @class(['bank-fields', 'd-none' => $row('channel') !== 'bank'])>
                    <input type="text" name="rows[{{ $b->id }}][bank_name]" value="{{ $row('bank_name') }}" class="form-control form-control-sm" placeholder="{{ __('taxigo.bank_name') }}" aria-label="{{ __('taxigo.bank_name') }}">
                    <input type="text" name="rows[{{ $b->id }}][bank_account_name]" value="{{ $row('bank_account_name') }}" class="form-control form-control-sm" placeholder="{{ __('taxigo.bank_account_name') }}" aria-label="{{ __('taxigo.bank_account_name') }}">
                    <input type="text" name="rows[{{ $b->id }}][bank_account_number]" value="{{ $row('bank_account_number') }}" class="form-control form-control-sm" placeholder="{{ __('taxigo.bank_account_number') }}" aria-label="{{ __('taxigo.bank_account_number') }}">
                  </div>
                @endif
              </td>
              <td class="text-right fw-semibold text-gray-800 tabular" data-example></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="split-total">
      <div class="d-flex align-items-baseline gap-2">
        <span class="kpi-label">{{ __('taxigo.total') }}</span>
        <span class="split-total-value" data-total>100%</span>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> {{ __('taxigo.save') }}</button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('split-form');
    var totalEl = form.querySelector('[data-total]');
    var fmt = function (n) { return Math.round(n).toLocaleString('fr-FR').replace(/ /g, ' '); };

    function refresh() {
      var total = 0;
      form.querySelectorAll('[data-row]').forEach(function (row) {
        var pct = parseFloat(row.querySelector('[data-percent]').value) || 0;
        total += pct;
        row.querySelector('[data-example]').textContent = fmt(10000 * pct / 100) + ' XAF';
      });
      total = Math.round(total * 100) / 100;
      totalEl.textContent = total + '%';
      totalEl.className = 'split-total-value ' + (Math.abs(total - 100) < 0.001 ? 'is-ok' : 'is-bad');
    }

    form.querySelectorAll('[data-percent]').forEach(function (el) { el.addEventListener('input', refresh); });
    form.querySelectorAll('[data-channel]').forEach(function (select) {
      select.addEventListener('change', function () {
        var row = select.closest('[data-row]');
        row.querySelector('[data-mobile]').classList.toggle('d-none', select.value === 'bank');
        row.querySelector('[data-bank]').classList.toggle('d-none', select.value !== 'bank');
      });
    });
    refresh();
  })();
</script>
@endpush
