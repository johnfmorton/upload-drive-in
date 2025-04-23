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
      <div class="prose bg-white rounded-lg shadow-md p-4 my-8 max-w-none">
        {!! $html !!}
    </div>
    </div>
</x-guest-layout>
@endauth
