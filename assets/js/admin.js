jQuery(document).ready( function() {

    kontainer_add_custom_tab()

    // Add custom tab when opening media library modal
    if ( wp.media ) {
        wp.media.view.Modal.prototype.on( "open", function() {
            kontainer_add_custom_tab()
        });
    }

})

function kontainer_add_custom_tab() {
    var btn = '';

    // On media modal
    if( jQuery('#menu-item-kontainer').length < 1 ) { // Only include button once.
        btn = '<button type="button" role="tab" class="media-menu-item" id="menu-item-kontainer" aria-selected="false" tabindex="-1">Kontainer</button>'
        jQuery('.media-router').append(btn)
    }

    // On media page
    if( jQuery('body.upload-php').length > 0 ) {

        if( jQuery('.page-title-action#menu-item-kontainer').length < 1 ) { // Only include button once.
            btn = '<a href="#" class="page-title-action" id="menu-item-kontainer">Add Kontainer image</a>'
            jQuery('.page-title-action').after(btn)
        }
    }
    
}

// On kontainer tab click
jQuery(document).on('click', '#menu-item-kontainer', function(){
    kontainer_init_window();
})

// On kontainer tab NOT click
jQuery(document).on('click', '.media-menu-item:not(#menu-item-kontainer)', function(){
    kontainer_destroy_window();
})


function receiveKontainer(imageData) {

    if (imageData !== '' && imageData != null) {
        kontainer_loader(true);
        var parsed = JSON.parse(imageData);

        if (Array.isArray(parsed)) {
            parsed.forEach((item, index) => {
                addImage(item, (index === parsed.length - 1))
            });
        } else {
            addImage(parsed, true);
        }
    }
}

function addImage(parsed, finish) {
    var img_url     = parsed.url;
    var img_type    = parsed.type;
    var img_desc    = parsed.description;
    var img_alt     = parsed.alt;
    var img_ext     = parsed.extension;

    var request = jQuery.ajax({
        url: "admin-post.php",
        type: "POST",
        data: {
            'img_url' : img_url,
            'img_type' : img_type,
            'img_desc' : img_desc,
            'img_alt' : img_alt,
            'img_ext' : img_ext,
            'action' : 'custom_action_hook'
        },
        dataType: "json"
    });

    request.done(function(resp) {
        if (resp.status === 'error') {
            alert(resp.msg)

        } else if (finish) {
            kontainer_loader(false);

            jQuery(".media-menu-item").eq(0).trigger("click");
            jQuery("#media-attachment-date-filters").trigger("change");

            // If on media page in list view
            if( jQuery('.table-view-list.media').length > 0 ) {
                location.reload();
            }
        }
    });

    request.fail(function(jqXHR, textStatus) {
        console.log( "Request failed: " + textStatus );
    });
}


function postBack(id) {
    //window.opener.receiveKontainer(id);
    top.receiveKontainer(id);
}


function kontainer_init_window () {

    if (window.addEventListener) {  // all browsers except IE before version 9
        window.addEventListener ("message", receiveMessage, false);
    }
    else if (window.attachEvent) {   // IE before version 9
        window.attachEvent("onmessage", receiveMessage);
    }

    var strWindowFeatures = "location=yes,height=800,width=1200,scrollbars=yes,status=yes";
    var URL = prep_url(kontainer_settings.kontainer_url) + '/?cmsMode=1';
    window.kontainer_window = window.open(URL, "_blank", strWindowFeatures);
}

function kontainer_destroy_window () {
    window.kontainer_window.close()
}

function prep_url(url) {
    if( url.indexOf("http") === 0 ) return url;
    return 'https://' + url;
}

function receiveMessage(event) {
    if (event) {
        postBack(event.data);
    }
}


function kontainer_loader(status = true) {

    if( status ) {
        var loader = document.createElement('div');
        loader.classList.add('kontainer_loader')
        loader.innerHTML = '<span class="kontainer__text">Processing image...</span><span class="kontainer__spinner"></span>'

        var frameContent = jQuery('.media-frame-content')
        if(frameContent.length > 0 ) {
            frameContent.append(loader)
        } else { // If on media page in list view
            jQuery('.table-view-list.media').append(loader)
        }
        
    } else {
        jQuery(document).find('.kontainer_loader').remove();
    }

}