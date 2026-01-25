<div class="max-w-3xl mx-auto p-2" @if($modalidade === 'real') wire:poll.1s="decrementarTempo" @endif>

    {{-- Cabeçalho FIXO --}}
    <div class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm p-3 rounded-lg shadow-md mb-4 border border-slate-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('cliente.dashboard') }}"
                    class="p-1.5 hover:bg-slate-100 rounded-full transition text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <img src="{{ asset('images/logo-proa.png') }}" alt="PROA" class="h-7 w-auto">

                <div class="bg-slate-100 px-2 py-1 rounded text-xs font-bold text-slate-600">
                    {{ count(array_filter($respostasUsuario)) }}/{{ count($questoes) }}
                </div>
            </div>

            <div class="flex items-center">
                @if($modalidade === 'real' && !$finalizado)
                    <div class="bg-slate-900 text-white px-3 py-1 rounded-md flex items-center">
                        {{-- Usando H:i:s para mostrar as 02:00:00 corretamente --}}
                        <span class="text-lg font-mono font-bold">{{ gmdate("H:i:s", $tempoRestante) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$finalizado)
        {{-- Listagem de Questões --}}
        @foreach($questoes as $index => $q)
            <div class="bg-white p-4 rounded-lg shadow-sm mb-3 border border-gray-200">
                <h3 class="font-bold mb-3 text-base text-slate-800 leading-tight">
                    {{ $index + 1 }}. {{ $q->enunciado }}
                </h3>

                <div class="grid grid-cols-1 gap-1.5">
                    @foreach(['a', 'b', 'c', 'd'] as $letra)
                        @php $coluna = "alternativa_" . $letra; @endphp
                        <button wire:click="responder({{ $q->id }}, '{{ $letra }}')" class="w-full text-left p-2.5 rounded-lg border text-sm transition-all
                                        {{ ($respostasUsuario[$q->id] ?? null) === $letra ? 'border-slate-800 bg-slate-100 font-bold' : 'border-gray-100 hover:border-slate-300' }}
                                        @if($modalidade === 'aprendizado' && isset($respostasUsuario[$q->id]))
                                            {{ $letra === $q->resposta_correta ? 'border-green-500 bg-green-50 text-green-700' : '' }}
                                            {{ $respostasUsuario[$q->id] === $letra && $letra !== $q->resposta_correta ? 'border-red-500 bg-red-50 text-red-700' : '' }}
                                        @endif">
                            <span class="uppercase">{{ $letra }})</span> {{ $q->$coluna }}
                        </button>
                    @endforeach
                </div>

                @if($modalidade === 'aprendizado' && isset($respostasUsuario[$q->id]))
                    <div
                        class="mt-2 text-xs font-bold {{ $respostasUsuario[$q->id] === $q->resposta_correta ? 'text-green-600' : 'text-red-600' }}">
                        {{ $respostasUsuario[$q->id] === $q->resposta_correta ? '✓ Resposta Correta!' : 'X Incorreto' }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="mt-6 mb-10">
            <button wire:click="finalizar"
                class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition">
                FINALIZAR SIMULADO
            </button>
        </div>
    @else
        {{-- TELA DE RESULTADO FINAL --}}
        <div class="bg-white p-8 rounded-2xl shadow-xl text-center border border-slate-200">
            <div class="mb-6">
                @if($resultado['aprovado'])
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full mb-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-slate-900">APROVADO!</h2>
                @else
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 text-red-600 rounded-full mb-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-slate-900">NÃO APROVADO</h2>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-slate-50 p-4 rounded-xl">
                    <span class="block text-slate-500 text-xs uppercase font-bold">Acertos</span>
                    <span class="text-2xl font-black text-slate-800">{{ $resultado['acertos'] }}/40</span>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl">
                    <span class="block text-slate-500 text-xs uppercase font-bold">Desempenho</span>
                    <span
                        class="text-2xl font-black text-slate-800">{{ number_format($resultado['porcentagem'], 0) }}%</span>
                </div>
            </div>

            <div class="space-y-3">
                {{-- CORREÇÃO AQUI TAMBÉM: --}}
                <a href="{{ route('cliente.dashboard') }}"
                    class="block w-full bg-slate-900 text-white font-bold py-4 rounded-xl hover:bg-slate-800 transition">
                    VOLTAR AO INÍCIO
                </a>
                <button onclick="window.location.reload()"
                    class="block w-full bg-slate-200 text-slate-700 font-bold py-4 rounded-xl hover:bg-slate-300 transition">
                    REFAZER SIMULADO
                </button>
            </div>
        </div>
    @endif
</div>