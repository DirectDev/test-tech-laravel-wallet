<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                <div class="text-base text-gray-400">@lang('Balance')</div>
                <div class="flex items-center pt-1">
                    <div class="text-2xl font-bold text-gray-900">
                        {{ \Illuminate\Support\Number::currencyCents($balance) }}
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                <h2 class="text-xl font-bold mb-6">@lang('Send money to a friend')</h2>
                <form method="POST" action="{{ route('send-money') }}" class="space-y-4">
                    @csrf

                    @if (session('money-sent-status') === 'success')
                        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                            <span class="font-medium">@lang('Money sent!')</span>
                            @lang(':amount were successfully sent to :name.', ['amount' => Number::currencyCents(session('money-sent-amount', 0)), 'name' => session('money-sent-recipient-name')])
                        </div>
                    @elseif (session('money-sent-status') === 'insufficient-balance')
                            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                                <span class="font-medium">@lang('Insufficient balance!')</span>
                                @lang('You can\'t send :amount to :name.', ['amount' => Number::currencyCents(session('money-sent-amount', 0)), 'name' => session('money-sent-recipient-name')])
                            </div>
                    @endif

                    <div>
                        <x-input-label for="recipient_email" :value="__('Recipient email')" />
                        <x-text-input id="recipient_email"
                                      class="block mt-1 w-full"
                                      type="email"
                                      name="recipient_email"
                                      :value="old('recipient_email')"
                                      required />
                        <x-input-error :messages="$errors->get('recipient_email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="amount" :value="__('Amount (€)')" />
                        <x-text-input id="amount"
                                      class="block mt-1 w-full"
                                      type="number"
                                      min="0"
                                      step="0.01"
                                      :value="old('amount')"
                                      name="amount"
                                      required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="reason" :value="__('Reason')" />
                        <x-text-input id="reason"
                                      class="block mt-1 w-full"
                                      type="text"
                                      :value="old('reason')"
                                      name="reason"
                                      required />
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>

                    <div class="flex justify-end mt-4">
                        <x-primary-button>
                            {{ __('Send my money !') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                <h2 class="text-xl font-bold mb-6">@lang('Recurring Transfers')</h2>
                <form method="POST" action="{{ route('recurring-transfer') }}" class="space-y-4">
                    @csrf

                    @if (session('recurring-transfer-status') === 'success')
                        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                            <span class="font-medium">@lang('Transfer ok!')</span>
                        </div>
                    @elseif (session('recurring-transfer-status') === 'error')
                        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                            <span class="font-medium">@lang('Transfer ko')</span>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="start-date" :value="__('start-date')" />
                        <x-text-input id="start-date"
                                      class="block mt-1 w-full"
                                      type="date"
                                      name="start_date"
                                      :value="old('start_date')"
                                      required />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="stop-date" :value="__('stop-date')" />
                        <x-text-input id="stop-date"
                                      class="block mt-1 w-full"
                                      type="date"
                                      name="stop_date"
                                      :value="old('stop_date')" />
                        <x-input-error :messages="$errors->get('stop_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="frequency" :value="__('frequency')" />
                        <x-text-input id="frequency"
                                      class="block mt-1 w-full"
                                      type="number"
                                      min="1"
                                      step="1"
                                      name="frequency"
                                      :value="old('frequency')"
                                      required />
                        <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="amount" :value="__('Amount (€)')" />
                        <x-text-input id="amount"
                                      class="block mt-1 w-full"
                                      type="number"
                                      min="0"
                                      step="0.01"
                                      :value="old('amount')"
                                      name="amount"
                                      required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="reason" :value="__('Reason')" />
                        <x-text-input id="reason"
                                      class="block mt-1 w-full"
                                      type="text"
                                      :value="old('reason')"
                                      name="reason" />
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>

                    <div class="flex justify-end mt-4">
                        <x-primary-button>
                            {{ __('Save !') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                <h2 class="text-xl font-bold mb-6">@lang('My Recurring Transfers')</h2>
                
                @if (session('recurring-transfer-delete-status') === 'success')
                    <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                        <span class="font-medium">@lang('Recurring transfer deleted successfully!')</span>
                    </div>
                @elseif (session('recurring-transfer-delete-status') === 'error')
                    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                        <span class="font-medium">@lang('Failed to delete recurring transfer.')</span>
                    </div>
                @endif
                
                @if($recurringTransfers->isEmpty())
                    <p class="text-gray-500">@lang('No recurring transfers yet.')</p>
                @else
                    <table class="w-full text-sm text-left text-gray-500 border border-gray-200">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                @lang('ID')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Start Date')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Stop Date')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Frequency (days)')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Amount')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Reason')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Status')
                            </th>
                            <th scope="col" class="px-6 py-3">
                                @lang('Actions')
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recurringTransfers as $transfer)
                            <tr class="bg-white border-b">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    {{$transfer->id}}
                                </th>
                                <td class="px-6 py-4">
                                    {{$transfer->start_date->format('Y-m-d')}}
                                </td>
                                <td class="px-6 py-4">
                                    {{$transfer->stop_date?->format('Y-m-d') ?? '—'}}
                                </td>
                                <td class="px-6 py-4">
                                    {{$transfer->frequency}}
                                </td>
                                <td class="px-6 py-4 font-semibold">
                                    {{Number::currency($transfer->amount, 'EUR')}}
                                </td>
                                <td class="px-6 py-4">
                                    {{$transfer->reason ?? '—'}}
                                </td>
                                <td class="px-6 py-4">
                                    @if($transfer->stop_date && $transfer->stop_date->isPast())
                                        <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded">@lang('Ended')</span>
                                    @elseif($transfer->start_date->isFuture())
                                        <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded">@lang('Scheduled')</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded">@lang('Active')</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="{{ route('recurring-transfer.delete', $transfer->id) }}" class="inline" onsubmit="return confirm('@lang('Are you sure you want to delete this recurring transfer?')')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-medium">
                                            @lang('Delete')
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                <h2 class="text-xl font-bold mb-6">@lang('Transactions history')</h2>
                <table class="w-full text-sm text-left text-gray-500 border border-gray-200">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            @lang('ID')
                        </th>
                        <th scope="col" class="px-6 py-3">
                            @lang('Reason')
                        </th>
                        <th scope="col" class="px-6 py-3">
                            @lang('Description')
                        </th>
                        <th scope="col" class="px-6 py-3">
                            @lang('Amount')
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($transactions as $transaction)
                        <tr class="bg-white border-b">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{$transaction->id}}
                            </th>
                            <td class="px-6 py-4">
                                {{$transaction->reason}}
                            </td>
                            <td class="px-6 py-4">
                                @if($transaction->is_transfer)
                                    @if ($transaction->type->isCredit())
                                        @lang(':name sent you :amount', [
                                            'amount' => Number::currencyCents($transaction->transfer->amount),
                                            'name' => $transaction->transfer->source->user->name,
                                        ])
                                    @else
                                        @lang('You sent :amount to :name', [
                                            'amount' => Number::currencyCents($transaction->transfer->amount),
                                            'name' => $transaction->transfer->target->user->name,
                                        ])
                                    @endif
                                @else
                                    @lang('--')
                                @endif
                            </td>
                            <td @class([
                                'px-6 py-4',
                                $transaction->type->isCredit() ? 'text-green-500' : 'text-red-500',
                            ])>
                                {{Number::currencyCents($transaction->type->isCredit() ? $transaction->amount : -$transaction->amount)}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
