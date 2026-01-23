<x-layouts.app>
    <div class="min-h-screen bg-slate-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-extrabold text-slate-900">Portal do Aluno - PROA</h1>
                <p class="mt-2 text-slate-600">Selecione o modo de estudo para iniciar seu simulado náutico</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-t-4 border-green-500 flex flex-col">
                    <div class="p-8 flex-grow">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">Modo Aprendizado</h2>
                        <ul class="space-y-3 text-slate-600 mb-6">
                            <li class="flex items-center">✓ Sem limite de tempo</li>
                            <li class="flex items-center">✓ Feedback imediato por questão</li>
                            <li class="flex items-center">✓ Ideal para fixação de conteúdo</li>
                        </ul>
                    </div>
                    <div class="p-6 bg-slate-50">
                        <a href="{{ route('simulado', ['modalidade' => 'aprendizado']) }}" 
                           class="block w-full text-center py-3 px-4 rounded-md bg-green-600 text-white font-bold hover:bg-green-700 transition">
                            COMEÇAR APRENDIZADO
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-t-4 border-blue-600 flex flex-col">
                    <div class="p-8 flex-grow">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">Simulado Real</h2>
                        <ul class="space-y-3 text-slate-600 mb-6">
                            <li class="flex items-center">✓ Cronômetro de 60 minutos</li>
                            <li class="flex items-center">✓ Resultado apenas ao final</li>
                            <li class="flex items-center">✓ Simula a prova oficial da Marinha</li>
                        </ul>
                    </div>
                    <div class="p-6 bg-slate-50">
                        <a href="{{ route('simulado', ['modalidade' => 'real']) }}" 
                           class="block w-full text-center py-3 px-4 rounded-md bg-blue-600 text-white font-bold hover:bg-blue-700 transition">
                            INICIAR SIMULADO REAL
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>