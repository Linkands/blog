<x-profile :sharedData="$sharedData" doctitle="Who {{ $sharedData['username'] }} follows">
    @include('profile-following-only')
</x-profile>
