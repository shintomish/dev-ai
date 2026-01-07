<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プロフィール
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium">プロフィール情報</h3>
                    <p class="mt-1 text-sm text-gray-600">アカウント情報を更新できます。</p>

                    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
                        @csrf
                        @method('patch')

                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">名前</label>
                            <input id="name" name="name" type="text" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div>
                            <label for="email" class="block font-medium text-sm text-gray-700">メールアドレス</label>
                            <input id="email" name="email" type="email" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>