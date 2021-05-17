'use strict';

(function($){
    $(function(){
        $('.favorite-btn.hide').hide();
        $('.favorite-btn').on('click',function(){
            let type = $(this).attr('class').replace('favorite-btn ','');
            $.ajax({
                type: 'post',
                url: uscesL10n.ajaxurl,
                data: {
                    'type' : type,
                    'post_id': uscesL10n.post_id,
                    'member_id': usces_favorite_array.ID,
                    'action': 'ajax_favorite'
                }
            }).then(
                function(data){
                    if( data == 'insert_error' ){
                        alert('すでに登録されています');
                    }else if( data == 'insert_success' ){
                        $('.favorite-btn.fav-in').hide();
                        $('.favorite-btn.fav-out').show();
                        alert('お気に入りに登録しました');
                    }else{
                        $('.favorite-btn.fav-in').show();
                        $('.favorite-btn.fav-out').hide();
                        alert('お気に入りから削除しました');
                    }
                },
                function(){
                    console.log('error');
                }
            )
        });
    });
})(jQuery);