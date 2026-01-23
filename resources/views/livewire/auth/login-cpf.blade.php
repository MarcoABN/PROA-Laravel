<div class="min-h-screen flex items-center justify-center bg-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900">
                PROA - Campeão Náutica
            </h2>
            <p class="mt-2 text-center text-sm text-slate-600">
                Digite seu CPF para acessar o simulado
            </p>
        </div>

        <form wire:submit.prevent="login" class="mt-8 space-y-6">
            @if (session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-md shadow-sm">
                <div>
                    <label for="cpf" class="sr-only">CPF</label>
                    <input wire:model="cpf" id="cpf" name="cpf" type="text" required 
                        class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-slate-500 focus:border-slate-500 sm:text-sm" 
                        placeholder="000.000.000-00">
                </div>
            </div>

            <div>
                <button type="submit" 
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-md text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                    ACESSAR PORTAL
                </button>
            </div>
        </form>
    </div>
</div>