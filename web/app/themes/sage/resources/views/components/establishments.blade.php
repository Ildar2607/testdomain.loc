<section class="establishments">
  <div class="container">
    <div class="establishments__box">
      @php
        $args = array(
            'posts_per_page'  => isset($posts_per_page) ? $posts_per_page : '-1' ,
            'post_type' => 'establishment',
            'orderby' => array('date ' => 'ASC')
          );
          $query = new WP_Query( $args );
           global $post;
    //print_r($query);
        $cur_terms = get_the_terms($post->ID, "Catering-tax");
      @endphp
      @if($query->have_posts())
        @while($query->have_posts())
          {{$query->the_post()}}
          <div class="establishments__cart">
            <img src="{{ get_the_post_thumbnail_url() }}" alt="" class="establishment__image">
            <h3 class="establishments__title">{{ get_the_title() }}</h3>
{{--            @foreach($cur_terms as $cur_term)--}}
{{--              <a class="post-preview__term" href="{{get_term_link($cur_term->term_id, $cur_term->taxonomy)}}">{{$cur_term->name}}</a>--}}
{{--            @endforeach--}}
            <div class="establishments__categorys">
              <span class="establishments__category">Тайская</span>
              <span class="establishments__category">Американская</span>
            </div>
          </div>
        @endwhile
      @endif

      {{wp_reset_postdata()}}
    </div>
  </div>
</section>
