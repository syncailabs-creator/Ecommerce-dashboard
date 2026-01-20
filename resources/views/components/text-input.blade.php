@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-primary-400 focus:ring-primary-400 rounded-lg shadow-sm text-slate-700']) }}>
