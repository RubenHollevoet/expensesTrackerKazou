/* go to top */
window.onscroll = function () {
    scrollFunction()
};

function scrollFunction() {
    //TODO: fix
    // if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    //     document.querySelector('.goToTop').classList.remove('down');
    //     $('.goHome').removeClass('large');
    // } else {
    //     document.querySelector('.goToTop').classList.add('down');
    //     $('.goHome').addClass('large');
    // }
}


function buildVvGpsCheckboxLogic() {
    var tasks = document.querySelectorAll('.vvgps_checklist > li');

    //add check boxes + add click event
    for (var i = 0, len = tasks.length; i < len; i++) {
        var checkBox_ = generateCheckbox(tasks[i].firstElementChild.dataset.id);
        tasks[i].insertBefore(checkBox_, tasks[i].firstElementChild);
    }

    //add 'add task' btn
    var checklists = document.querySelectorAll('.vvgps_checklist');

    for (var j = 0, length = checklists.length; j < length; j++) {
        var li = document.createElement('li');
        var addBox = document.createElement('span');
        addBox.classList.add('btn', 'btn-success', 'fa', 'fa-plus', 'check_task');
        addBox.addEventListener('click', function (evt) {
            createCheckItem(evt.currentTarget.parentElement, function (li_, checkItemTxt) {
                //text added callback
                var hash = simpleHash(checkItemTxt + Math.random());
                loadCheckItem(li_.parentElement, checkItemTxt, hash);
                loStoItemsAdd(li_.parentElement.id, hash, checkItemTxt);
                loStoChecksSave(); //reload json to add new key
            });
        });

        li.appendChild(addBox);
        checklists[j].appendChild(li);
    }

    //load additional lines from locale storage
    loStoItemsRecover();

    //read checked lines from locale storage
    loStoChecksRecover();
}

function generateCheckbox(id) {
    var checkBox = document.createElement('span');
    checkBox.classList.add('btn', 'btn-default', 'fa', 'fa-check', 'check_task');
    if (id) checkBox.id = 'c-' + id;

    checkBox.addEventListener('click', function (e) {
        e.currentTarget.classList.toggle('btn-success');
        e.currentTarget.classList.toggle('btn-default');

        var textElement = e.currentTarget.parentElement.lastElementChild;

        e.currentTarget.classList.contains('btn-default') ? textElement.classList.remove('lineThrough') : textElement.classList.add('lineThrough');

        loStoChecksSave(); //save all checkbox states in locale storage
    });

    return checkBox;
}

function createCheckItem(parent_, textComplete) {
    var inputBox = document.createElement('input');
    inputBox.type = 'text';
    inputBox.classList.add('form-control');
    inputBox.setAttribute('style', 'max-width: 500px');
    parent_.appendChild(inputBox);
    inputBox.focus();

    function handleTextInput() {
        var txt = inputBox.value;

        if (txt) {
            textComplete(parent_, txt);
        }

        inputBox.remove();
    }

    inputBox.addEventListener('focusout', function () {
        handleTextInput();
    });

    //remove focus and trigger event when pressing 'ENTER'
    inputBox.addEventListener('keyup', function (evt) {
        if (evt.keyCode === 13) {
            inputBox.blur();
        }
    })
}

function loadCheckItem(ul_, txt, hash) {
    var checkBox_ = generateCheckbox(hash);

    var removeBtn_ = document.createElement('span');
    removeBtn_.classList.add('btn', 'btn-danger', 'fa', 'fa-times');
    removeBtn_.addEventListener('click', function (evt) {
        var key = evt.currentTarget.parentElement.firstElementChild.id.slice(2);
        loStoChecksRemoveKey(key);
        loStoItemsRemove(key);
        evt.currentTarget.parentElement.remove();
    });

    var txt_ = document.createElement('span');
    txt_.innerText = txt;

    var li_ = document.createElement('li');
    li_.appendChild(checkBox_);
    li_.appendChild(removeBtn_);
    li_.appendChild(txt_);
    ul_.insertBefore(li_, ul_.lastElementChild);
}

function loStoChecksSave() {
    var checks = document.querySelectorAll('.check_task');

    var json = JSON.parse(localStorage.getItem('vvgps_checks'));
    if (!json) json = {};

    for (var i = 0, len = checks.length; i < len; i++) {
        var check_ = checks[i];
        if (check_.id) {
            json[check_.id.substr(2)] = check_.classList.contains('btn-success');
        }
    }

    localStorage.setItem('vvgps_checks', JSON.stringify(json));
}

function loStoChecksRecover() {
    var json = JSON.parse(localStorage.getItem('vvgps_checks'));
    if (json) {
        for (var btn_id in json) {
            var btn_ = document.getElementById('c-' + btn_id);
            if (btn_ && json[btn_id]) {
                btn_.click();
            }
        }
    }
}

function loStoChecksRemoveKey(key) {
    var json = JSON.parse(localStorage.getItem('vvgps_checks'));
    delete json[key];
    localStorage.setItem('vvgps_checks', JSON.stringify(json));
}

function loStoItemsAdd(parent, hash, text) {
    var json = JSON.parse(localStorage.getItem('vvgps_items'));
    if (!json) json = [];

    json.push( {
        parent: parent,
        hash: hash,
        text: text
    });

    localStorage.setItem('vvgps_items', JSON.stringify(json));
}

function loStoItemsRecover() {
    var json = JSON.parse(localStorage.getItem('vvgps_items'));
    if (json) {
        for (var i = 0; i < json.length; i++) {
            var checkItem = json[i];
            var ul_ = document.getElementById(checkItem.parent);
            if (ul_) {checkItem
                loadCheckItem(ul_, checkItem.text, checkItem.hash)
            }
        }
    }
}

function loStoItemsRemove(key) {
    var json = JSON.parse(localStorage.getItem('vvgps_items'));

    key = json.findIndex(function(e) {
        return e.hash === key;
    });

    json.splice(key, 1);
    localStorage.setItem('vvgps_items', JSON.stringify(json));
}

init();

function init() {
    buildVvGpsCheckboxLogic();
}


// generate a simple hash
function simpleHash(s) {
    /* Simple hash function. */
    var a = 1, c = 0, h, o;
    if (s) {
        a = 0;
        /*jshint plusplus:false bitwise:false*/
        for (h = s.length - 1; h >= 0; h--) {
            o = s.charCodeAt(h);
            a = (a << 6 & 268435455) + o + (o << 14);
            c = a & 266338304;
            a = c !== 0 ? a ^ c >> 21 : a;
        }
    }
    return String(a);
};

/* SMOOTH SCROLLING */
$('a[href*="#"]')           // Select all links with hashes
    .not('[href="#"]')      // Remove links that don't actually link to anything
    .not('[href="#0"]')     //
    .click(function (event) {
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname
        ) {
            var target = $(this.hash);  // Figure out element to scroll to
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');

            if (target.length) {// Does a scroll target exist?
                event.preventDefault();// Only prevent default if animation is actually gonna happen
                // var headerOffset = ($('header').css('position') === 'fixed') ? $('header').css('height').slice(0, -2) : 0;
                $('html, body').animate({
                    scrollTop: target.offset().top
                }, 500, function () {
                });
            }
        }
    });
