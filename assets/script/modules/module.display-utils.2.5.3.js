/**
 * This file is written by Kalahiri in 2020-2021
 * and may be used under GPLv3 license.
 * https://www.gnu.org/licenses/gpl-3.0.html
 */

"use strict";

/**
 * User Experience Object: - animations
 * Namespace: display
 */
( function ( display ) {

    /**
     * Scroll smoothly to top of the page
     */
    display.scrollToTop = function( gototop = true ) {
        if( 0 === window.scrollY ) return;  // Already at the top.
        let scrollToPosition;
        if( gototop ) {
            scrollToPosition = 0;
        } else {
            var rect = document.getElementById('container-waste-water').getBoundingClientRect();
            scrollToPosition = window.scrollY + rect.top - 10;
        }
        window.scrollTo({
            top: scrollToPosition,
            behavior: "smooth"
        });
    };




 /**
     * Slide Down or Slide Up an element in Vanilla JS without using jQuery
     * Notice: The animation sliding up or down doesn't work on borders, so do not use thick borders for a nice animation
     * 
     * @param { element } element to show or to hide
     * @param { string } toggle can be 'show', 'hide' or 'toggle'.
     * @param { int } speed is the animation speed sliding up or down in milliseconds
     * @param { int } maxHeight The maxheight of the element if shown. maxHeight is necessary for the animation to work
     *                          Don't set it too much above expected height for a nice transition.
     * @param { element } referenceElement van be used to get the maxHeight from, if a similar element is available.
     * @return { bool } current status of the element. if visible return true, else return false. 
     */

  display.slideUpDown = function( element, toggle = 'show', speed = 4000, maxHeight = 300, referenceElement = null ) {

    if ( null != referenceElement ) {
        maxHeight = referenceElement.offsetHeight + 50; // some margin to be safe. It nibbles only a bit of the animation.
    }

    if( 'toggle' == toggle ) {
        toggle = ( element.classList.contains( 'slide-up-down-hide' ) ? 'show' : 'hide' );
    }

    const transitionDuration = speed/1000;
    element.style.transitionDuration = transitionDuration + 's';

    if( 'show' == toggle ) {
        setTimeout( function() { 
            element.style.visibility= 'visible';
            element.classList.remove( 'slide-up-down-hide' );
            element.style.maxHeight = maxHeight + 'px';
            element.classList.add( 'slide-up-down-show' );
        }, 10 );
    } else {
        setTimeout( function() { 
            element.classList.add( 'slide-up-down-hide' );
            element.style.maxHeight = '0px';
            element.classList.remove( 'slide-up-down-show' );
            setTimeout( function() { 
                element.style.visibility= 'hidden';
            }, speed + 10 );
        }, 10 );
    }

}





    /**
     * Slide Down or Slide Up an element in Vanilla JS without using jQuery
     * 
     * @param { element } element 
     * @param { string | bool } direction can be true|false or 'down'|'up'
     * @param { int } maxHeight The maxheight of the element if shown.
     *                          Don't set it too much above expected height for a nice transition.
     * @return { bool } current status of the element. if visible return true, else return false. 
     */
    /* UNUSED */
    /*
    display.slideUpDown = function( element, direction = 'down', maxHeight = 300 ) {
        element.classList.remove( 'hide' );
        // element.style.maxHeight = '300px';
        //direction = ( element.classList.contains( 'hide' ) ) ? true : false;
        if( direction ) { // show

            console.log( 'show: ' + direction);
            //console.log( element );
            element.classList.remove( 'hide' );
            console.log( element );
            //element.style.display = 'block';
            //element.style.maxHeight = 300 ;
            // element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            //element.style.maxHeight = '300px';
            setTimeout( function( ) {
                console.log( 'settimeout');

                element.style.transition = 'max-height 0.4s ease 0'; 
                element.style.maxHeight = '300px';
                console.log( element );
            }.bind( element ), 1);
           //console.log( element );

        } else { // hide
            console.log( 'show: ' + direction);
            console.log( element );
            // element.addEventListener( 'transitionend', onTransitionComplete );
            element.style.transition = 'max-height 0.4s ease 0'; 
            element.style.maxHeight = '0px';
            setTimeout( function( ) {
               //console.log( element );
                element.classList.add( 'hide' );
                //element.style.removeProperty("maxHeight");

                console.log( element );
            }.bind( element ), 2000 );
           // 
            //element.style.removeProperty("display");



        }
        return;
        console.log( 'start' );
       //  console.log( element );
        // add on first run a transition listener. Add class 'hide' at end of transition to hide element completely.
        if( ! element.getAttribute('data-listener-transitionend') ) {
            element.addEventListener( 'transitionend', ( event ) => {
                console.log( 'mxH: ' + maxHeight );
                console.log( event.target.style.maxHeight );
                if( '0px' === event.target.style.maxHeight ) {
                    event.target.style.display = 'none';
                }  


//                const visible = ( '0px' === event.target.style.maxHeight ) ? false : true;
//                 if( ! visible ) {
//                     event.target.classList.add( 'hide' );
//                 }

            });
            element.setAttribute('data-listener-transitionend', true);
        }

        direction = ( true === direction || 'down' === direction ) ? true : false;

        if( direction ) {
            console.log( 'mxH: ' + maxHeight );
            element.classList.remove( 'hide' );
            setTimeout( function() {   // setting "display: block" ruins transition, so start transition 1 ms later.

                element.style.maxHeight = maxHeight + 'px';
            }.bind( element ), 1 );
        } else {
            element.style.maxHeight = '0';
        }
        console.log( element );
        return direction;
    };

    */

    /* only on hide */
    /* UNUSED
    function onTransitionComplete( event ) {
        console.log( 'transitionComplete ');
        console.log( event.target );
        event.target.removeEventListener( 'transitionend', onTransitionComplete );
        event.target.style.display = 'none';
        //event.target.style.removeProperty("max-height");
        console.log( event.target );

    }
*/





   /**
     * Toggle a popup between visible and invisible
     * @param { string } id:The ID of the popup to be shown
     * @param { int } posX,posY:The coordinates of the click,
     *               to position the popup right next to or beneath the clicked position
     */
    display.popupToggle = function( id, posX = 0, posY = 0, fullscreen = false ) {
        const popup = document.getElementById( id );
        if( popup.classList.contains( 'is-visible' ) ) {
            popup.classList.remove( 'is-visible' );
            setTimeout( function(){
                popup.style.display = 'none';
            },600 );
        } else {
            popup.style.display = 'block';
            let bodyWidth = Math.max( document.body.scrollWidth,document.body.offsetWidth );
            if( fullscreen ) {
                console.log( popup);
                let screenWidth = Math.max( document.body.scrollWidth,document.body.offsetWidth );
                popup.style.right = '1em';
                popup.style.left = 'auto';
                popup.style.height = 'auto'; // ( window.innerHeight * .95 ) + 'px';
                if(bodyWidth < 300 ) {
                    popup.style.width = ( window.innerWidth * .95 ) + 'px';
                } else {
                    popup.style.width = ( window.innerWidth * .75 ) + 'px';
                    popup.querySelector( '.popup-contents' ).style.marginTop = '2em';
                }
            } else if( posX > 0 ) {

                if( posX + ( 300 / 2 ) < bodyWidth ) { // Default popup width == 300
                    let newPosX = posX / 2 + window.scrollX;
                    popup.style.right = 'auto';
                    popup.style.left = newPosX + 'px';
                } else {
                    popup.style.right = '0';
                    popup.style.left = 'auto';
                }
            }
            let newTop = ( posY > 0 ) ? 10 + posY + window.scrollY : 20 + window.scrollY ;
            popup.style.top = newTop  + 'px';
            popup.classList.add('is-visible');
        }
    };



   /**
     * Show a popup for a shorttime
     */
    display.popupShowShort = function( id, posX = 0, posY = 0, fullscreen = false, target = '', touchScreen = false ) {
        if( ! target ) {
            target = document.body;
        }
        const boundingClientRect = target.getBoundingClientRect();
        let left = ( 0 === posX ) ? boundingClientRect.left + ( boundingClientRect.width * 0.1 ) : posX;
        let top = ( 0 === posY ) ? boundingClientRect.top + ( boundingClientRect.height * 0.2 ) : posY;
        let popupShort;
        // event.sourceCapabilities.firesTouchEvents == true if touch event has fired
        popupShort = ( touchScreen )
                    ? document.getElementById( id + '-mobile' )
                    : document.getElementById( id );
        popupShort.style.left = left + 'px';
        popupShort.style.top = top + 'px';
        popupShort.style.display = 'block';
        popupShort.classList.add('is-visible');
        setTimeout( function() {
            popupShort.classList.remove('is-visible');
            setTimeout( function() {
                popupShort.style.display = 'none';
            }, 2000);
        }, 3000 );

    }







    /**
     * responsive:move some elements on smaller screens
     */
    display.responsivePositions= function() {
        if ( window.innerWidth < 600 ) { // move text block to end on smaller screens
            document.getElementById('share-container')
                        .insertAdjacentElement("beforebegin", document.getElementById('qena-container') );
            //goToTop = false;
            // CL.maps.moveLegend( 'up' );
        } else { // move text block (back) on larger screens
            document.getElementById('charts-container-wrapper')
                        .insertAdjacentElement("beforeend", document.getElementById('qena-container') );
            //goToTop = true;
            // CL.maps.moveLegend( 'up' );
        }
        return;
    };



}( window.CL.display = window.CL.display || {} ) );
/** User Experience Object / Namespace: display */




