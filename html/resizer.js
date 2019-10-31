var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var Resizer = function () {
    function Resizer(containerSelector, resizerOptions) {
        if (resizerOptions === void 0) {
            resizerOptions = {};
        }
        this.containerSelector = containerSelector;
        this.resizerOptions = resizerOptions;
        this.offsetX = 0;
        this.dragging = false;
        this.options = _extends(Resizer.defaultOptions, this.resizerOptions, {});
        this.container = Resizer.getElement(containerSelector);
        this.target = this.container.firstElementChild;
        if (this.container.Resizer) {
            this.remove();
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
    Resizer.createHandle = function (handleClass) {
        var el = document.createElement('div');
        el.dataset.rzHandle = handleClass || '';
        el.style.cursor = 'ew-resize';
        return el;
    };
    Resizer.createGhost = function () {
        var el = document.createElement('div');
        el.style.position = 'absolute';
        el.style.top = '0';
        el.style.bottom = '0';
        el.style.display = 'none';
        el.style.zIndex = '99999';
        return el;
    };
    Resizer.prototype.remove = function () {
        delete this.container.Resizer;
        this.container.style.position = null;
        this.container.querySelector('[data-rz-handle]').remove();
        this.target.style.flex = null;
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
        this.handle = Resizer.createHandle();
        this.ghost = Resizer.createGhost();
        this.handle.appendChild(this.ghost);
        this.container.insertBefore(this.handle, this.target.nextElementSibling);
    };
    Resizer.prototype.setDragging = function (value) {
        var w = this.handleX * 100 / window.innerWidth;
        if (value === void 0) {
            value = true;
        }
        if (this.dragging) {
            this.ghost.style.display = 'none';
            this.target.style.flexBasis = w + "%";
            this.target.nextElementSibling.nextElementSibling.style.flexBasis = (100 - w) + "%";
            this.resizerOptions.callback(w);
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
    Resizer.prototype.onDown = function (e) {
        e.preventDefault();
        if (!this.dragging) {
            this.offsetX = e.offsetX;
            this.setHandleX(e.pageX - this.container.getBoundingClientRect().left - this.offsetX);
            this.setDragging(true);
            this.container.addEventListener('mouseup', this.mup);
            this.container.addEventListener('mousemove', this.mmove);
        }
    };
    Resizer.prototype.onUp = function (e,t) {
        e.preventDefault();
        if (t.dragging) {
            t.setHandleX(e.pageX - t.container.getBoundingClientRect().left - t.offsetX);
            t.setDragging(false);
            this.container.removeEventListener('mouseup', this.mup);
            this.container.removeEventListener('mousemove', this.mmove);
        }
    };
    Resizer.prototype.onMove = function (e,t) {
        e.preventDefault();
        if (t.dragging) {
            var x = e.pageX - t.container.getBoundingClientRect().left - t.offsetX;
            if (e.shiftKey) {
                x = Math.ceil(x / 20) * 20;
            }
            t.setHandleX(x);
        }
    };
    return Resizer;
}();
Resizer.defaultOptions = {
    width: 8
};
//# sourceMappingURL=resizer.js.map
