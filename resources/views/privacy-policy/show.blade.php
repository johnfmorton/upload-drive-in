@auth
<x-app-layout>
    <div class="container p-4 mx-auto">
      <div class="prose bg-white rounded-lg shadow-md p-4 my-8 max-w-none">
          {!! $html !!}
      </div>
    </div>
</x-app-layout>
@else
<x-guest-layout>
    <div class="container p-4 mx-auto">
      <div class="prose bg-white rounded-lg shadow-md py-4 px-6 my-8 max-w-none">
        {{-- Return to home button --}}
        <a href="/" class="text-blue-500 hover:text-blue-700 mb-6 block">Return to home</a>
        {!! $html !!}
        <a href="/" class="text-blue-500 hover:text-blue-700 mt-6 block">Return to home</a>
    </div>
    </div>
</x-guest-layout>
@endauth
