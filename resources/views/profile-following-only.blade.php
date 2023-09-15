<div class="list-group">
    @foreach ($followingTheseUsers as $followedUser)
        <a href="/profile/{{$followedUser->userBeingFollowed->username}}" class="list-group-item list-group-item-action">
            <img class="avatar-tiny" src="{{$followedUser->userBeingFollowed->avatar}}" />
            {{$followedUser->userBeingFollowed->username}}
        </a>
    @endforeach
</div>