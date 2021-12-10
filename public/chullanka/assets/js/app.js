/*  =UTILITIES
----------------------------------------------------------------------------- */
let log = function(x) {
    if(typeof console != 'undefined') console.log(x);
};

var handleHash = {};
var bindEvent = (function() {
    if (window.addEventListener) {
        return function(el, type, fn, capture) {
            el.addEventListener(type, function(e) {
                fn(e);
                handleHash[type] = handleHash[type] || [];
                handleHash[type].push(arguments.callee);
            }, capture);
        };
    } else if (window.attachEvent) {
        return function(el, type, fn, capture) {
            el.attachEvent('on' + type, function(e) {
                fn(e);
                handleHash[type] = handleHash[type] || [];
                handleHash[type].push(arguments.callee);
            });
        };
    }
})();
var unbindEvent = (function(){
    if (window.addEventListener) {
        return function(el, type ) {
            if(handleHash[type]){
                var i = 0, len = handleHash[type].length;
                for (i; i<len ; i += 1){
                    el.removeEventListener(type, handleHash[type][i]);
                }
            };
        };
    } else if (window.attachEvent) {
        return function(el, type) {
            if(handleHash[type]){
                var i = 0, len = handleHash[type].length;
                for (i; i<len ; i += 1){
                    el.detachEvent('on' + type, handleHash[type][i]);
                }
            };
        };
    }
})();

if(window.NodeList && !NodeList.prototype.forEach) 
{
    NodeList.prototype.forEach = Array.prototype.forEach;
}
if(window.HTMLCollection && !HTMLCollection.prototype.forEach) 
{
    HTMLCollection.prototype.forEach = Array.prototype.forEach;
}

/*  =WINDOW.ONLOAD
----------------------------------------------------------------------------- */
bindEvent(document, 'DOMContentLoaded', function(e) {
    funcTopAnnounce();
    funcStickyHeader();
    funcScrollTo();
    funcMainMenu();
    funcFoldable();
    funcOpenFilters();
    funcLoadMoreProds();
});

/* TopAnnounce
------------------------- */
var funcTopAnnounce = function()
{
    var $topAnnounce = document.querySelector('#topAnnounce');
    if($topAnnounce)
    {
        var _closeBtn = $topAnnounce.querySelector('.close');

        // interaction
        bindEvent(_closeBtn, 'click', function(e) {
            $topAnnounce.classList.add('hidden');
            $topAnnounce.style.marginTop = '-' + $topAnnounce.clientHeight + 'px';
            setTimeout(function() {
                $topAnnounce.style.display = 'none';
            }, 500);
        });
    }
}

/* StickyHeader
------------------------- */
var funcStickyHeader = function()
{
    const $header = document.querySelector('header');
    if($header)
    {

        /*const observer = new IntersectionObserver( 
        ([e]) => e.target.classList.toggle('is-pinned', e.intersectionRatio < 1),
            { threshold: [1] }
        );
        observer.observe($header);*/

        window.onscroll = function() 
        {
            if (document.body.scrollTop > 220 || document.documentElement.scrollTop > 220) {
                $header.classList.add('is-pinned');
            } else {
                $header.classList.remove('is-pinned');
            }
        }

        // Mobile SearchBox
        const $searchBox = document.querySelector('#searchBox');
        if($searchBox)
        {
            $searchBox.querySelector('label').addEventListener('click', function(e) {
                $header.classList.toggle('search-visible');
            });
        }
    }
}


/* Scroll to an anchor
------------------------- */
var funcScrollTo = function()
{
    var _topDecay = 100;//=stickyHeader!
    //document.querySelectorAll('.jsToAnchor').forEach(function($target) {
    document.querySelectorAll('a[href^="#"]').forEach(function($target) {
        $target.addEventListener('click', function(event) {
            event.preventDefault();
            
            var _target = $target.getAttribute('href');
            if(_target != '#')
            {
                var $anchor = document.querySelector(_target);
                window.scrollTo({
                    top: $anchor.offsetTop - _topDecay,
                    behavior: 'smooth'
                  });
            }
              
            /*setTimeout(function () {
                anchor.scrollIntoView();
            }, 1000);*/
        });
    });
}


/* MainMenu
------------------------- */
var funcMainMenu = function()
{
    var $mainNav = document.querySelector('header nav');
    if($mainNav)
    {
        var _openBtn = document.querySelector('#openMainMenu');
        var _closeBtn = $mainNav.querySelector('#closeMainMenu');
        var _navBlock = $mainNav.querySelector('#navBlock');
        
        // Open main menu
        _openBtn.addEventListener('click', function(e) {
            $mainNav.classList.add('shown');
        });

        // Close main menu
        var _closeMainMenu = function()
        {
            $mainNav.classList.remove('shown');
            $mainNav.querySelector('ul').style.left = '0';
            $mainNav.querySelectorAll('.shown').forEach(function($elmt) {
                $elmt.classList.remove('shown');
            });
        }
        _closeBtn.addEventListener('click', _closeMainMenu);
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') _closeMainMenu();
            //(e.ctrlKey && e.altKey) 
            if(e.altKey) 
            {
                //if(e.keyCode == 77) //= m
                if(e.keyCode == 81) //= q
                {
                    $mainNav.classList.add('shown');
                }
            }
        });

        // submenus
        $mainNav.querySelectorAll('.openSubnav').forEach(function($subLnk, _index, _obj) {
            $subLnk.addEventListener('click', function(e) {
                e.preventDefault();
                var $_target = $subLnk.parentNode.nextElementSibling;
                $_target.scrollTop = 0;
                $_target.classList.add('shown');

                var _level = $_target.getAttribute('data-level');
                $mainNav.querySelector('ul').style.left = '-' + (_level * 100) + '%';
                _navBlock.scrollTop = 0;
            });
        });
        $mainNav.querySelectorAll('.closeSubNav').forEach(function($returnLnk, _index, _obj) {
            $returnLnk.addEventListener('click', function(e) {
                var $_target = $returnLnk.parentNode.parentNode;
                $_target.classList.remove('shown');

                var _level = $_target.getAttribute('data-level');
                $mainNav.querySelector('ul').style.left = '-' + ((_level - 1) * 100) + '%';
                _navBlock.scrollTop = 0;
            });
        });
    }
}

/* Foldable elements
------------------------- */
var funcFoldable = function()
{
    document.querySelectorAll('.fold').forEach(function(link) {
        bindEvent(link, 'click', function(e){
            e.preventDefault();

            var _target = link.getAttribute('href');
            if(_target && (_target[0] == '#'))
            {
                if(_target == '#')
                {
                    //find next
                    _target = link.nextElementSibling;
                }
                else
                {
                    // find by selector #
                    _target = document.querySelector(_target);
                }
            }
            else
            {
                if(link.nextElementSibling.classList.contains('foldable'))
                {
                    //find next
                    _target = link.nextElementSibling;
                }
            }

            if(_target)
            {
                var _shown = _target.classList.contains('shown');
                if(!_shown)
                {
                    _target.classList.add('shown');
                    _target.style.height = _target.scrollHeight + 'px';
                    link.classList.add('on');
                    if(_label = link.getAttribute('data-on'))
                    {
                        link.textContent = _label;
                    }
                }
                else
                {
                    _target.classList.remove('shown');
                    _target.style.height = '0';
                    link.classList.remove('on');
                    if(_label = link.getAttribute('data-off'))
                    {
                        link.textContent = _label;
                    }
                }
            }
        })
    });
}

/* Show filters on mobile
------------------------- */
var funcOpenFilters = function()
{
    var $filters = document.querySelector('.mobileFilters');
    if($filters)
    {
        document.querySelector('.openFilters').addEventListener('click', function(e) {
            $filters.classList.remove('hidden');
        });
        $filters.querySelector('.svg-icon-close').addEventListener('click', function(e) {
            $filters.classList.add('hidden');
        });
    }
}

/* AJAX load product in list
------------------------- */
var funcLoadMoreProds = function()
{
    var $btnLoadMore = document.querySelector('#jsLoadMoreProducts');
    var $prodZone = document.querySelector('#productList .list');
    if($btnLoadMore && $prodZone)
    {
        bindEvent($btnLoadMore, 'click', function(e) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'inc/ajax-addprods.php');
            //xhr.onreadystatechange = function () {
            xhr.onload = function () {
                if (xhr.readyState === xhr.DONE) {
                    if (xhr.status === 200) {
                        var _content = document.createElement('div');
                        _content.innerHTML = xhr.responseText;
                        _content.querySelectorAll('.prodInList').forEach(function($elmt) {
                            $prodZone.appendChild($elmt);
                        });
                    } else {
                        console.log('Error: ' + xhr.status);
                    }
                }
            };
            xhr.send(null);
        });
    }
}