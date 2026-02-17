@extends('admin::layout')

@section('title', 'Plan de ' . $user->name)

@section('content')
<div class="p-8">
    <a href="{{ route('admin.users') }}" class="back-link">
        ← Volver a usuarios
    </a>
    <h1 class="page-title mb-1">{{ $user->name }}</h1>
    <p class="page-subtitle mb-6">Plan y límites por módulo</p>

    @if($modules->isEmpty())
        <div class="admin-card">
            <p class="text-sm text-gray-400">Este usuario no tiene módulos activos. <a href="{{ route('admin.users.modules', $user) }}" class="text-primary-400 hover:text-primary-300">Activar módulos →</a></p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.users.update-plan', $user) }}">
            @csrf
            <div class="space-y-4">
                @foreach($modules as $module)
                <div class="admin-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-white">{{ $module['name'] }}</h3>
                        <select
                            name="modules[{{ $module['slug'] }}][plan]"
                            class="admin-form-input !w-auto text-sm"
                            onchange="toggleLimits(this, '{{ $module['slug'] }}')"
                        >
                            <option value="free" {{ $module['plan'] === 'free' ? 'selected' : '' }}>Free</option>
                            <option value="premium" {{ $module['plan'] === 'premium' ? 'selected' : '' }}>Premium</option>
                            <option value="custom" {{ $module['plan'] === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>

                    <div
                        id="limits-{{ $module['slug'] }}"
                        class="{{ $module['plan'] === 'premium' ? 'hidden' : '' }}"
                    >
                        {{-- Limits --}}
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($module['free_limits'] as $key => $defaultValue)
                                @php
                                    $currentValue = $module['limits'][$key] ?? $defaultValue;
                                    $label = match($key) {
                                        'max_habits' => 'Máx. hábitos',
                                        'max_entities' => 'Máx. entidades',
                                        'max_messages_per_day' => 'Máx. mensajes/día',
                                        default => $key,
                                    };
                                @endphp
                                <div>
                                    <label class="admin-form-label">{{ $label }}</label>
                                    <input
                                        type="number"
                                        name="modules[{{ $module['slug'] }}][limits][{{ $key }}]"
                                        value="{{ $currentValue }}"
                                        min="1"
                                        class="admin-form-input text-sm"
                                        {{ $module['plan'] === 'free' ? 'readonly' : '' }}
                                        id="input-{{ $module['slug'] }}-{{ $key }}"
                                    >
                                </div>
                            @endforeach
                        </div>
                        @if($module['plan'] === 'free')
                            <p class="text-xs text-gray-500 mt-2">Los límites del plan Free son fijos. Cambia a Custom para personalizarlos.</p>
                        @endif

                        {{-- Features --}}
                        @if(!empty($module['free_features']))
                            <div class="mt-4 pt-4 border-t border-gray-800">
                                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Features</h4>
                                <div
                                    id="features-{{ $module['slug'] }}"
                                    class="grid grid-cols-2 md:grid-cols-3 gap-2"
                                >
                                    @foreach($module['free_features'] as $featureKey => $defaultEnabled)
                                        @php
                                            $isEnabled = $module['features'][$featureKey] ?? $defaultEnabled;
                                            $featureLabel = match($featureKey) {
                                                'weekly_view' => 'Vista semanal',
                                                'routines' => 'Rutinas',
                                                'sparklines' => 'Sparklines',
                                                'notes' => 'Notas',
                                                'badges' => 'Insignias',
                                                'streak_freeze' => 'Congelación racha',
                                                'celebrations' => 'Celebraciones',
                                                'charts' => 'Gráficos',
                                                'ai_analyzer' => 'Análisis IA',
                                                'templates' => 'Plantillas',
                                                'calendar_view' => 'Vista calendario',
                                                'vacation_mode' => 'Modo vacaciones',
                                                'reminders' => 'Recordatorios',
                                                default => str_replace('_', ' ', ucfirst($featureKey)),
                                            };
                                            $isFreeDefault = $defaultEnabled;
                                        @endphp
                                        <label class="flex items-center gap-2 py-1.5 px-2 rounded-lg cursor-pointer hover:bg-white/[0.03] transition-colors feature-label-{{ $module['slug'] }}">
                                            <input
                                                type="checkbox"
                                                name="modules[{{ $module['slug'] }}][features][{{ $featureKey }}]"
                                                value="1"
                                                {{ $isEnabled ? 'checked' : '' }}
                                                {{ $module['plan'] === 'free' ? 'disabled' : '' }}
                                                class="rounded border-gray-600 bg-gray-800 text-primary-500 focus:ring-primary-500 focus:ring-offset-0 feature-cb-{{ $module['slug'] }}"
                                            >
                                            <span class="text-sm text-gray-300">{{ $featureLabel }}</span>
                                            @if($isFreeDefault)
                                                <span class="text-[10px] text-green-500">free</span>
                                            @else
                                                <span class="text-[10px] text-yellow-500">premium</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                                @if($module['plan'] === 'free')
                                    <p class="text-xs text-gray-500 mt-2">Las features del plan Free son fijas. Cambia a Custom para personalizarlas.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6">
                <button type="submit" class="admin-btn-primary">
                    Guardar cambios
                </button>
            </div>
        </form>
    @endif
</div>

<script>
function toggleLimits(select, slug) {
    const limitsDiv = document.getElementById('limits-' + slug);
    const numberInputs = limitsDiv.querySelectorAll('input[type="number"]');
    const featureCheckboxes = limitsDiv.querySelectorAll('.feature-cb-' + slug);

    if (select.value === 'premium') {
        limitsDiv.classList.add('hidden');
    } else {
        limitsDiv.classList.remove('hidden');
    }

    numberInputs.forEach(input => {
        input.readOnly = select.value === 'free';
    });

    featureCheckboxes.forEach(cb => {
        cb.disabled = select.value === 'free';
    });
}
</script>
@endsection
