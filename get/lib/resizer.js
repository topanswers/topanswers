var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var Resizer = function () {
    function Resizer(containerSelector, resizerOptions) {
        if (resizerOptions === void 0) {
            resizerOptions = {};
        }
        this.containerSelector = containerSelector;
        this.resizerOptions = resizerOptions;
        this.offsetX = 0;
        this.offsetY = 0;
        this.dragging = false;
        this.options = _extends(Resizer.defaultOptions, this.resizerOptions, {});
        this.container = Resizer.getElement(containerSelector);
        this.target_one = this.container.firstElementChild;
        this.target_two = this.container.firstElementChild.nextElementSibling;
        if (this.container.Resizer) {
            this.remove();
        }
        var container_style = window.getComputedStyle ? getComputedStyle(this.container, null) : this.container.currentStyle;
        if (container_style.flexDirection === 'column' || container_style.flexDirection === 'column-reverse') {
            this.orientation = 'horizontal';
        } else {
            this.orientation = 'vertical';
        }
        this.setup();
    }
    Resizer.removeBySelector = function (input) {
        var container = Resizer.getElement(input);
        if (container.hasOwnProperty('Resizer')) {
            container.Resizer.remove();
        } else {
            throw new Error('Resizer doesn\'t exist on element');
        }
    };
    Resizer.getElement = function (input) {
        var el;
        if (!input) {
            throw new Error('Missing param, should be an element or selector');
        }
        if (typeof input === 'string') {
            el = document.querySelector(input);
            if (!el) {
                throw new Error("Can not find element from selector " + input);
            }
        } else {
            el = input;
        }
        return el;
    };
    Resizer.createHandle = function (orientation,options) {
        var el = document.createElement('div');
        el.dataset.rzHandle = orientation || '';
        el.style.flex = '0 0 '+options.width+'px';
        el.style.background = options.colour;
        if (orientation === 'horizontal') {
            el.style.height = options.width+'px';
            el.style.width = '100%';
            el.style.cursor = 'ns-resize';
        } else {
            el.style.height = '100%';
            el.style.width = options.width+'px';
            el.style.cursor = 'ew-resize';
        }
        return el;
    };
    Resizer.createGhost = function (orientation,options) {
        var el = document.createElement('div');
        el.style.position = 'absolute';
        el.style.background = options.ghost_colour;
        if (orientation === 'horizontal') {
            el.style.height = options.width+'px';
            el.style.left = '0';
            el.style.right = '0';
        } else {
            el.style.width = options.width+'px';
            el.style.top = '0';
            el.style.bottom = '0';
        }
        el.style.display = 'none';
        el.style.zIndex = '99999';
        return el;
    };
    Resizer.prototype.remove = function () {
        delete this.container.Resizer;
        this.container.style.position = null;
        this.container.querySelector('[data-rz-handle]').remove();
        this.target_one.style.flex = null;
        this.target_two.style.flex = null;
    };
    Resizer.prototype.setup = function () {
        var _this = this;
        this.mup = function (e) { return _this.onUp(e,_this); };
        this.mmove = function (e) { return _this.onMove(e,_this); };
        this.setupDom();
        this.handle.addEventListener('mousedown', function (e) {
            return _this.onDown(e);
        });
        this.container.Resizer = this;
    };
    Resizer.prototype.setupDom = function () {
        this.container.style.position = 'relative';
        this.handle = Resizer.createHandle(this.orientation,this.options);
        this.ghost = Resizer.createGhost(this.orientation,this.options);
        this.handle.appendChild(this.ghost);
        this.container.insertBefore(this.handle, this.target_two);
    };
    Resizer.prototype.setDragging = function (value) {
        var w = this.handleX;
        var y = this.handleY;
        var style = window.getComputedStyle ? getComputedStyle(this.handle, null) : this.handle.currentStyle;
        var offset_top = parseInt(style.marginTop) + (this.handle.offsetHeight / 2);
        var offset_right = parseInt(style.marginRight) + (this.handle.offsetWidth / 2);
        var offset_bottom = parseInt(style.marginBottom) + (this.handle.offsetHeight / 2);
        var offset_left = parseInt(style.marginLeft) + (this.handle.offsetWidth / 2);
        if (value === void 0) {
            value = true;
        }
        if (this.dragging) {
            this.ghost.style.display = 'none';
            if (this.orientation === 'horizontal') {
                if (this.options.mode === 'pixel') {
                    this.container.firstElementChild.style.flexBasis = (y - offset_top) + 'px';
                    if (this.options.full_length) { this.target_two.style.flexBasis = (this.container.clientHeight - (y + offset_bottom)) + 'px'; }
                    if (typeof this.resizerOptions.callback === 'function') { this.resizerOptions.callback(y); }
                } else {
                    this.container.firstElementChild.style.flexBasis = (y - offset_top) * 100 / this.container.clientHeight + '%';
                    if (this.options.full_length) { this.target_two.style.flexBasis = (this.container.clientHeight - (y + offset_bottom)) * 100 / this.container.clientHeight + '%'; }
                    if (typeof this.resizerOptions.callback === 'function') { this.resizerOptions.callback(y * 100 / this.container.clientHeight); }
                }
            } else {
                if (this.options.mode === 'pixel') {
                    this.target_one.style.flexBasis = (w - offset_left) + 'px';
                    if (this.options.full_length) { this.target_two.style.flexBasis = (this.container.clientWidth - (w + offset_right)) + 'px'; }
                    if (typeof this.resizerOptions.callback === 'function') { this.resizerOptions.callback(w); }
                } else {
                    this.target_one.style.flexBasis = (w - offset_left) * 100 / this.container.clientWidth + '%';
                    if (this.options.full_length) { this.target_two.style.flexBasis = (this.container.clientWidth - (w + offset_right)) * 100 / this.container.clientWidth + '%'; }
                    if (typeof this.resizerOptions.callback === 'function') { this.resizerOptions.callback(w * 100 / this.container.clientWidth); }
                }
            }
        } else {
            this.ghost.style.display = 'block';
        }
        return this.dragging = value;
    };
    Resizer.prototype.setHandleX = function (value) {
        if (value < 0) {
            value = 0;
        }
        if (value > this.container.clientWidth) {
            value = this.container.clientWidth;
        }
        this.ghost.style.left = value + "px";
        return this.handleX = value;
    };
    Resizer.prototype.setHandleY = function (value) {
        if (value < 0) { value = 0; }
        if (value > this.container.clientHeight) { value = this.container.clientHeight; }
        this.ghost.style.top = value + "px";
        return this.handleY = value;
    };
    Resizer.prototype.onDown = function (e) {
        e.preventDefault();
        if (!this.dragging) {
            if (this.orientation === 'horizontal') {
                this.offsetY = e.offsetY;
                this.setHandleY(e.pageY - this.container.getBoundingClientRect().top - this.offsetY);
            } else if (this.orientation === 'horizontal') {
                this.offsetX = e.offsetX;
                this.setHandleX(e.pageX - this.container.getBoundingClientRect().left - this.offsetX);
            }
            this.setDragging(true);
            this.container.addEventListener('mouseup', this.mup);
            this.container.addEventListener('mousemove', this.mmove);
        }
    };
    Resizer.prototype.onUp = function (e,t) {
        e.preventDefault();
        if (t.dragging) {
            if (this.orientation === 'horizontal') { t.setHandleY(e.pageY - t.container.getBoundingClientRect().top - t.offsetY); } else { t.setHandleX(e.pageX - t.container.getBoundingClientRect().left - t.offsetX); }
            t.setDragging(false);
            this.container.removeEventListener('mouseup', this.mup);
            this.container.removeEventListener('mousemove', this.mmove);
        }
    };
    Resizer.prototype.onMove = function (e,t) {
        e.preventDefault();
        if (t.dragging) {
            if (this.orientation === 'horizontal') {
                var y = e.pageY - t.container.getBoundingClientRect().top - t.offsetY;
                if (e.shiftKey) {
                  y = Math.ceil(y / 20) * 20;
                }
                t.setHandleY(y);
            } else {
                var x = e.pageX - t.container.getBoundingClientRect().left - t.offsetX;
                if (e.shiftKey) {
                  x = Math.ceil(x / 20) * 20;
                }
                t.setHandleX(x);
            }
        }
    };
    return Resizer;
}();
Resizer.defaultOptions = {
    colour: 'rgba(0, 0, 0, 0.75)',
    ghost_colour: 'rgba(0, 0, 0, 0.5)',
    mode: 'percent',
    full_length: false,
    width: 6
};
