/*  =UTILITIES
----------------------------------------------------------------------------- */
var _topDecay = 100;//=stickyHeader!

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
                //handleHash[type].push(arguments.callee);
            }, capture);
        };
    } else if (window.attachEvent) {
        return function(el, type, fn, capture) {
            el.attachEvent('on' + type, function(e) {
                fn(e);
                handleHash[type] = handleHash[type] || [];
                //handleHash[type].push(arguments.callee);
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
    funcPopin();
    funcMainMenu();
    funcFoldable();
    funcOpenFilters();
    funcLoadMoreProds();
    funcMountingPop();
});

/* TopAnnounce
------------------------- */
window.funcTopAnnounce = function()
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
window.funcStickyHeader = function()
{
    const $header = document.querySelector('header');
    if($header)
    {
        var _scrollTop = window.scrollY || document.documentElement.scrollTop;
        var _lastScrollTop = _scrollTop;
        var _scrollDir = 1;
        var _lastScrollDir = _scrollDir;
        var _topLimit = 30;
        var $topAnnounce = document.querySelector('#topAnnounce');

        /*const observer = new IntersectionObserver( 
        ([e]) => e.target.classList.toggle('is-pinned', e.intersectionRatio < 1),
            { threshold: [1] }
        );
        observer.observe($header);*/

        window.onscroll = function() 
        {
            if($topAnnounce && $topAnnounce.classList.contains('hidden'))
            {
                _topLimit = 10;
            }

            //_scrollTop = document.body.scrollTop || document.documentElement.scrollTop;
            _scrollTop = window.scrollY || document.documentElement.scrollTop;
            _scrollDir = (_scrollTop < _lastScrollTop) ? -1 : 1;
            _lastScrollTop = _scrollTop;
            if(_scrollDir != _lastScrollDir)
            {
                if($header.classList.contains('scroll-up')) $header.classList.remove('scroll-up');
                if($header.classList.contains('scroll-down')) $header.classList.remove('scroll-down');
                $header.classList.add((_scrollDir == -1) ? 'scroll-up' : 'scroll-down');
                _lastScrollDir = _scrollDir;
            }

            if (_scrollTop > _topLimit) {
                $header.classList.add('is-pinned');
            } else {
                $header.classList.remove('is-pinned');
            }
        }

        // Mobile SearchBox
        const $searchBox = document.querySelector('#searchBox');
        if($searchBox)
        {
            bindEvent($searchBox.querySelector('label'), 'click', function(event) {
            //$searchBox.querySelector('label').addEventListener('click', function(e) {
                $header.classList.toggle('search-visible');
            });
        }
    }
}


/* Scroll to an anchor
------------------------- */
window.funcScrollTo = function()
{
    //document.querySelectorAll('.jsToAnchor').forEach(function($link) {
    document.querySelectorAll('a[href^="#"]').forEach(function($link) {
        bindEvent($link, 'click', function(event) {
            event.preventDefault();
            
            var _target = $link.getAttribute('href');
            if(_target != '#')
            {
                var $anchor = document.querySelector(_target);
                if($link.classList.contains('topopin'))
                {
                    $anchor.classList.remove('hidden');
                    document.querySelector('body').classList.add('overflow');
                }
                else
                {
                    if($anchor.classList.contains('frame') && !$anchor.querySelector('.fold').classList.contains('on'))
                    {
                        //pour ouvrir l'onglet avant le scroll
                        $anchor.querySelector('.fold').click();
                    }
                    window.scrollTo({
                        top: $anchor.offsetTop - _topDecay,
                        behavior: 'smooth'
                    });
                }
            }
              
            /*setTimeout(function () {
                anchor.scrollIntoView();
            }, 1000);*/
        });
    });
}


/* Popin
------------------------- */
window.funcPopin = function()
{
    document.querySelectorAll('.popin').forEach(function($popin) {
        bindEvent($popin.querySelector('.popinside'), 'click', function(e) {
        //$popin.querySelector('.popinside').addEventListener('click', function(e) {
            e.stopPropagation();
            return false;
        });

        // Close popin
        var _closePopin = function()
        {
            $popin.classList.add('hidden');
            document.querySelector('body').classList.remove('overflow');
        }
        bindEvent($popin, 'click', function(e) {
        //$popin.addEventListener('click', function(e) {
            e.stopPropagation();
            _closePopin();
        });
        bindEvent($popin.querySelector('.closePopin'), 'click', function(e) {
        //$popin.querySelector('.closePopin').addEventListener('click', function(e) {
            e.preventDefault();
            _closePopin();
        });
        bindEvent(document, 'keydown', function(e) {
        //document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') _closePopin();
        });
    });
}


/* MainMenu
------------------------- */
window.funcMainMenu = function()
{
    var $mainNav = document.querySelector('header nav');
    if($mainNav)
    {
        var _openBtn = document.querySelector('#openMainMenu');
        var _closeBtn = $mainNav.querySelector('#closeMainMenu');
        var _navBlock = $mainNav.querySelector('#navBlock');
        
        // Open main menu
        bindEvent(_openBtn, 'click', function(e) {
        //_openBtn.addEventListener('click', function(e) {
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
        bindEvent(_closeBtn, 'click', _closeMainMenu);
        //_closeBtn.addEventListener('click', _closeMainMenu);
        bindEvent(document, 'keydown', function(e) {
        //document.addEventListener('keydown', function(e) {
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
            bindEvent($subLnk, 'click', function(e) {
            //$subLnk.addEventListener('click', function(e) {
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
            bindEvent($returnLnk, 'click', function(e) {
            //$returnLnk.addEventListener('click', function(e) {
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
window.funcFoldable = function()
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
                    if(link.getAttribute('data-on'))
                    {
                        link.textContent = link.getAttribute('data-on');
                    }
                }
                else
                {
                    _target.classList.remove('shown');
                    _target.style.height = '0';
                    link.classList.remove('on');
                    if(link.getAttribute('data-off'))
                    {
                        link.textContent = link.getAttribute('data-off');
                    }
                }
            }
        });

        var _hTitle = link.parentNode.querySelector('h2');
        if(_hTitle)
        {
            bindEvent(_hTitle, 'click', function(e){
                link.click();
            });
        };
    });
}

/* Show filters on mobile
------------------------- */
window.funcOpenFilters = function()
{
    var $filters = document.querySelector('.mobileFilters');
    if($filters)
    {
        bindEvent(document.querySelector('.openFilters'), 'click', function(e) {
        //document.querySelector('.openFilters').addEventListener('click', function(e) {
            $filters.classList.remove('hidden');
        });
        bindEvent($filters.querySelector('.svg-icon-close'), 'click', function(e) {
        //$filters.querySelector('.svg-icon-close').addEventListener('click', function(e) {
            $filters.classList.add('hidden');
        });
        bindEvent($filters.querySelector('#showProducts'), 'click', function(e) {
        //$filters.querySelector('#showProducts').addEventListener('click', function(e) {
            $filters.classList.add('hidden');
            var $anchor = document.querySelector('#productList');
            window.scrollTo({
                top: $anchor.offsetTop - _topDecay + 40,
                behavior: 'smooth'
            });
        });
    }
}

/* AJAX load product in list
------------------------- */
window.funcLoadMoreProds = function()
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


/* MountingPop
------------------------- */
window.funcMountingPop = function()
{
    var $mountingPop = document.querySelector('#mountingPop');
    if($mountingPop)
    {
        //temp
        //$mountingPop.classList.remove('hidden');
        //document.querySelector('body').classList.add('overflow');
        //temp

        var _openBtn = document.querySelector('#mountingOptions #mounting-yes');
        
        // Open main menu
        bindEvent(_openBtn, 'click', function(e) {
            $mountingPop.classList.remove('hidden');
            document.querySelector('body').classList.add('overflow');
        });

        // validate btn
        bindEvent($mountingPop.querySelector('.button-wrapper .btn'), 'click', function(e) {
            log("On verifie si tout est rempli comme il faut !");

            //si oui
            if(true)
            {
                $mountingPop.classList.add('hidden');
                document.querySelector('body').classList.remove('overflow');
            }
        });
    }
}