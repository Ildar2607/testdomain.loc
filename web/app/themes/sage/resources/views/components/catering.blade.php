<div class="catering">
  <div class="container">
    <div class="catering__box">
      <div class="catering__left">
        <ul class="catering__list">
          @php $terms = get_terms(array(
            'taxonomy' => 'Catering-tax',
            'hide_empty' => false,
            'orderby' => 'id',
            ));
          @endphp
          @foreach( $terms as $term)
            <li class="catering__item">{{ $term->name }}</li>
          @endforeach
        </ul>
      </div>
      <div class="catering__right">
        @component('components.dropdown', ['id' => 'town'])
        @endcomponent
      </div>
    </div>
  </div>
</div>
