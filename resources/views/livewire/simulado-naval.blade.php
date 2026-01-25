<div class="max-w-3xl mx-auto p-2" @if($modalidade === 'real') wire:poll.1s="decrementarTempo" @endif>
    
    {{-- Cabeçalho Fixo com Suporte a 2 Horas --}}
    <div class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm p-3 rounded-lg shadow-md mb-4 border border-slate-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('cliente.dashboard') }}" class="p-1.5 hover:bg-slate-100 rounded-full transition text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <img src="{{ asset('images/logo-proa.png') }}" alt="PROA" class="h-7 w-auto">
                
                <div class="bg-slate-100 px-2 py-1 rounded text-xs font-bold text-slate-600">
                    {{ count(array_filter($respostasUsuario)) }}/{{ count($questoes) }}
                </div>
            </div>
            
            <div class="flex items-center">
                @if($modalidade === 'real' && !$finalizado)
                    <div class="bg-slate-900 text-white px-3 py-1 rounded-md flex items-center">
                        {{-- Formato H:i:s para suportar as 2 horas corretamente --}}
                        <span class="text-lg font-mono font-bold">{{ gmdate("H:i:s", $tempoRestante) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$finalizado)
        @foreach($questoes as $index => $q)
            <div class="bg-white p-4 rounded-lg shadow-sm mb-3 border border-gray-200">
                <h3 class="font-bold mb-3 text-base text-slate-800 leading-tight">
                    {{ $index + 1 }}. {{ $q->enunciado }}
                </h3>
                
                <div class="grid grid-cols-1 gap-1.5">
                    {{-- Renderiza as alternativas na ordem sorteada no Mount --}}
                    @foreach($ordemAlternativas[$q->id] as $altIndex => $letraOriginal)
                        @php 
                            $coluna = "alternativa_" . $letraOriginal;
                            // Converte índice (0, 1, 2...) em letra visual (A, B, C...)
                            $letraExibicao = chr(65 + $altIndex); 
                        @endphp

                        <button 
                            wire:click="responder({{ $q->id }}, '{{ $letraOriginal }}')"
                            class="w-full text-left p-2.5 rounded-lg border text-sm transition-all
                            {{ ($respostasUsuario[$q->id] ?? null) === $letraOriginal ? 'border-slate-800 bg-slate-100 font-bold' : 'border-gray-100 hover:border-slate-300' }}
                            @if($modalidade === 'aprendizado' && isset($respostasUsuario[$q->id]))
                                {{ $letraOriginal === $q->resposta_correta ? 'border-green-500 bg-green-50 text-green-700' : '' }}
                                {{ $respostasUsuario[$q->id] === $letraOriginal && $letraOriginal !== $q->resposta_correta ? 'border-red-500 bg-red-50 text-red-700' : '' }}
                            @endif">
                            <span class="font-bold uppercase">{{ $letraExibicao }})</span> {{ $q->$coluna }}
                        </button>
                    @endforeach
                </div>

                @if($modalidade === 'aprendizado' && isset($respostasUsuario[$q->id]))
                    <div class="mt-2 text-xs font-bold {{ $respostasUsuario[$q->id] === $q->resposta_correta ? 'text-green-600' : 'text-red-600' }}">
                        {{ $respostasUsuario[$q->id] === $q->resposta_correta ? '✓ Resposta Correta!' : 'X Incorreto' }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="mt-6 mb-10">
            <button wire:click="finalizar" class="w-full bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 transition">
                FINALIZAR SIMULADO
            </button>
        </div>
    @else
        {{-- TELA DE RESULTADO FINAL (Mantida conforme original) --}}
        <div class="bg-white p-8 rounded-2xl shadow-xl text-center border border-slate-200">
            {{-- ... conteúdo do resultado ... --}}
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-slate-50 p-4 rounded-xl">
                    <span class="block text-slate-500 text-xs uppercase font-bold">Acertos</span>
                    <span class="text-2xl font-black text-slate-800">{{ $resultado['acertos'] }}/40</span>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl">
                    <span class="block text-slate-500 text-xs uppercase font-bold">Desempenho</span>
                    <span class="text-2xl font-black text-slate-800">{{ number_format($resultado['porcentagem'], 0) }}%</span>
                </div>
            </div>
            <a href="{{ route('cliente.dashboard') }}" class="block w-full bg-slate-900 text-white font-bold py-4 rounded-xl hover:bg-slate-800 transition">
                VOLTAR AO INÍCIO
            </a>
        </div>
    @endif
</div>