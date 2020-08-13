<form class="header__form {{ $classes or '' }}">
  @include('icon::search')
  <input id="search" class="search {{ $classes or '' }}" type="text" placeholder="Поиск заведений и блюд">
</form>
