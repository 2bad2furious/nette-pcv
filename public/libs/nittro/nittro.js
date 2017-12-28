window.Nette = {noInit: !0}, function () {
    function nextTick(fn) {
        global.setImmediate ? setImmediate(fn) : global.importScripts ? setTimeout(fn) : (queueId++, queue[queueId] = fn, global.postMessage(queueId, "*"))
    }

    function Deferred(resolver) {
        "use strict";

        function thennable(ref, cb, ec, cn) {
            if (2 == state) return cn();
            if ("object" != typeof val && "function" != typeof val || "function" != typeof ref) cn(); else try {
                var cnt = 0;
                ref.call(val, function (v) {
                    cnt++ || (val = v, cb())
                }, function (v) {
                    cnt++ || (val = v, ec())
                })
            } catch (e) {
                val = e, ec()
            }
        }

        function fire() {
            var ref;
            try {
                ref = val && val.then
            } catch (e) {
                return val = e, state = 2, fire()
            }
            thennable(ref, function () {
                state = 1, fire()
            }, function () {
                state = 2, fire()
            }, function () {
                try {
                    1 == state && "function" == typeof fn ? val = fn(val) : 2 == state && "function" == typeof er && (val = er(val), state = 1)
                } catch (e) {
                    return val = e, finish()
                }
                val == self ? (val = TypeError(), finish()) : thennable(ref, function () {
                    finish(3)
                }, finish, function () {
                    finish(1 == state && 3)
                })
            })
        }

        if ("function" != typeof resolver && void 0 != resolver) throw TypeError();
        if ("object" != typeof this || this && this.then) throw TypeError();
        var fn, er, self = this, state = 0, val = 0, next = [];
        self.promise = self, self.resolve = function (v) {
            return fn = self.fn, er = self.er, state || (val = v, state = 1, nextTick(fire)), self
        }, self.reject = function (v) {
            return fn = self.fn, er = self.er, state || (val = v, state = 2, nextTick(fire)), self
        }, self._d = 1, self.then = function (_fn, _er) {
            if (1 != this._d) throw TypeError();
            var d = new Deferred;
            return d.fn = _fn, d.er = _er, 3 == state ? d.resolve(val) : 4 == state ? d.reject(val) : next.push(d), d
        }, self["catch"] = function (_er) {
            return self.then(null, _er)
        };
        var finish = function (type) {
            state = type || 4, next.map(function (p) {
                3 == state && p.resolve(val) || p.reject(val)
            })
        };
        try {
            "function" == typeof resolver && resolver(self.resolve, self.reject)
        } catch (e) {
            self.reject(e)
        }
        return self
    }

    global = this;
    var queueId = 1, queue = {}, isRunningTask = !1;
    global.setImmediate || global.addEventListener("message", function (e) {
        if (e.source == global) if (isRunningTask) nextTick(queue[e.data]); else {
            isRunningTask = !0;
            try {
                queue[e.data]()
            } catch (e) {
            }
            delete queue[e.data], isRunningTask = !1
        }
    }), Deferred.resolve = function (value) {
        if (1 != this._d) throw TypeError();
        return value instanceof Deferred ? value : new Deferred(function (resolve) {
            resolve(value)
        })
    }, Deferred.reject = function (value) {
        if (1 != this._d) throw TypeError();
        return new Deferred(function (resolve, reject) {
            reject(value)
        })
    }, Deferred.all = function (arr) {
        function done(e, v) {
            if (v) return d.resolve(v);
            if (e) return d.reject(e);
            var unresolved = arr.reduce(function (cnt, v) {
                return v && v.then ? cnt + 1 : cnt
            }, 0);
            0 == unresolved && d.resolve(arr), arr.map(function (v, i) {
                v && v.then && v.then(function (r) {
                    return arr[i] = r, done(), r
                }, done)
            })
        }

        if (1 != this._d) throw TypeError();
        if (!(arr instanceof Array)) return Deferred.reject(TypeError());
        var d = new Deferred;
        return done(), d
    }, Deferred.race = function (arr) {
        function done(e, v) {
            if (v) return d.resolve(v);
            if (e) return d.reject(e);
            var unresolved = arr.reduce(function (cnt, v) {
                return v && v.then ? cnt + 1 : cnt
            }, 0);
            0 == unresolved && d.resolve(arr), arr.map(function (v, i) {
                v && v.then && v.then(function (r) {
                    done(null, r)
                }, done)
            })
        }

        if (1 != this._d) throw TypeError();
        if (!(arr instanceof Array)) return Deferred.reject(TypeError());
        if (0 == arr.length) return new Deferred;
        var d = new Deferred;
        return done(), d
    }, Deferred._d = 1, "undefined" != typeof module ? module.exports = Deferred : global.Promise = global.Promise || Deferred
}();
var _context = function () {
    function resolveUrl(u) {
        return resolver || (resolver = elem("a")), resolver.href = u, resolver.href
    }

    function isRelative(u) {
        try {
            var len = /^https?:\/\/.+?(\/|$)/i.exec(loc.href)[0].length;
            return u.substr(0, len) === loc.href.substr(0, len)
        } catch (err) {
            return !1
        }
    }

    function xhr(u) {
        return new promise(function (fulfill, reject) {
            var req, m;
            req = isRelative(u) ? xhrFactory() : xdrFactory(), req.open("GET", u, !0);
            var f = function () {
                m && clearTimeout(m), fulfill(req)
            }, r = function () {
                m && clearTimeout(m), reject(req)
            };
            "onsuccess" in req ? (req.onsuccess = f, req.onerror = r) : win.XDomainRequest !== undefined && req instanceof win.XDomainRequest ? (req.onload = f, req.onerror = r) : req.onreadystatechange = function () {
                4 === req.readyState && (200 === req.status ? f() : r())
            }, req.send(), m = setTimeout(function () {
                if (req.readyState && req.readyState < 4) try {
                    req.abort()
                } catch (err) {
                }
                m = null, r()
            }, REQ_TIMEOUT)
        })
    }

    function exec(s, t, u) {
        var e;
        t = t ? t.replace(/\s*;.*$/, "").toLowerCase() : u.match(/\.(?:less|css)/i) ? "text/css" : "text/javascript", "text/css" === t ? (e = elem("style"), e.type = t, u = u.replace(/[^\/]+$/, ""), s = s.replace(/url\s*\(('|")?(?:\.\/)?(.+?)\1\)/, function (m, q, n) {
            return q || (q = '"'), n.match(/^(?:(?:https?:)?\/)?\//) ? "url(" + q + n + q + ")" : "url(" + q + resolveUrl(u + n) + q + ")"
        }), e.styleSheet ? e.styleSheet.cssText = s : e.appendChild(doc.createTextNode(s)), doc.head.appendChild(e)) : (e = elem("script"), e.type = "text/javascript", e.text = s, doc.head.appendChild(e).parentNode.removeChild(e))
    }

    function lookup(s, c) {
        var i = map.names.indexOf(s);
        if (i > -1) return map.classes[i];
        for (var n, r = t, p = s.split("."); p.length;) {
            if (n = p.shift(), r[n] === undefined) {
                if (!c) throw new Error(s + " not found in context");
                r[n] = {}
            }
            r = r[n]
        }
        return map.names.push(s), map.classes.push(r), r
    }

    function lookupClass(o) {
        if ("object" == typeof o && o.constructor !== Object && (o = o.constructor), "function" != typeof o && "object" != typeof o) throw new Error("Cannot lookup class name of non-object");
        var i = map.classes.indexOf(o);
        return i !== -1 && map.names[i]
    }

    function load() {
        var u, a, p = promise.resolve(!0);
        for (a = 0; a < arguments.length; a++) "function" == typeof arguments[a] ? p = p.then(function (f) {
            return function () {
                return invoke(f)
            }
        }(arguments[a])) : "string" == typeof arguments[a] && (u = resolveUrl(arguments[a]), loaded.indexOf(u) === -1 && (p = loading[u] ? p.then(function (p) {
            return function () {
                return p
            }
        }(loading[u])) : loading[u] = function (p, u) {
            return new promise(function (f, r) {
                xhr(u).then(function (xhr) {
                    p.then(function () {
                        exec(xhr.responseText, xhr.getResponseHeader("Content-Type"), u), delete loading[u], loaded.push(u), f()
                    }, r)
                })
            })
        }(p, u)));
        return a = {
            then: function (fulfilled, rejected) {
                return p.then(function () {
                    fulfilled && invoke(fulfilled)
                }, function () {
                    rejected && invoke(rejected)
                }), a
            }
        }
    }

    function invoke(ns, f, i) {
        i === undefined && "function" == typeof ns && (i = f, f = ns, ns = null), ns ? nsStack.unshift(ns, ns = lookup(ns, !0)) : (ns = t, nsStack.unshift(null, ns));
        var p, c, r, params = f.length ? f.toString().match(/^function\s*\((.*?)\)/i)[1].split(/\s*,\s*/) : [],
            args = [];
        for (p = 0; p < params.length; p++) if ("context" === params[p]) args.push(api); else if ("_NS_" === params[p]) args.push(ns); else if ("undefined" === params[p]) args.push(undefined); else if (i !== undefined && params[p] in i) c = i[params[p]], "string" == typeof c && (c = lookup(c)), args.push(c); else if (ns[params[p]] !== undefined) args.push(ns[params[p]]); else {
            if (t[params[p]] === undefined) throw new Error('"' + params[p] + '" not found in context');
            args.push(t[params[p]])
        }
        return r = f.apply(ns, args), nsStack.shift(), nsStack.shift(), r
    }

    function register(constructor, name) {
        var ns = name.split(/\./g), key = ns.pop();
        return ns.length ? ns = lookup(ns.join("."), !0) : nsStack.length && null !== nsStack[0] ? (name = nsStack[0] + "." + name, ns = nsStack[1]) : ns = t, ns[key] = constructor, map.names.push(name), map.classes.push(constructor), api
    }

    function __ns() {
        arguments.length ? nsStack.unshift(arguments[0], arguments[1]) : (nsStack.shift(), nsStack.shift())
    }

    function extend(parent, constructor, proto) {
        proto || (proto = constructor, constructor = parent, parent = null), parent ? "string" == typeof parent && (parent = lookup(parent)) : parent = Object;
        var tmp = function () {
        };
        return tmp.prototype = parent.prototype, constructor.prototype = new tmp, constructor.prototype.constructor = constructor, constructor.Super = parent, proto && (proto.hasOwnProperty("STATIC") && proto.STATIC && copyProps(constructor, proto.STATIC), copyProps(constructor.prototype, proto)), constructor
    }

    function mixin(target, source, map) {
        return "string" == typeof target && (target = lookup(target)), "string" == typeof source && (source = lookup(source)), source.hasOwnProperty("STATIC") && source.STATIC && merge(target, source.STATIC), copyProps(target.prototype, source, map), target
    }

    function copyProps(target, source, map) {
        var key;
        for (key in source) source.hasOwnProperty(key) && "STATIC" !== key && (target[map && key in map ? map[key] : key] = source[key])
    }

    function merge(a, b) {
        for (var key in b) b.hasOwnProperty(key) && (a.hasOwnProperty(key) && "object" == typeof a[key] && "object" == typeof b[key] && a[key] ? b[key] && merge(a[key], b[key]) : a[key] = b[key])
    }

    var api, undefined, t = {}, loaded = [], loading = {}, REQ_TIMEOUT = 3e4, doc = document, elem = function (n) {
        return doc.createElement(n)
    }, win = window, loc = win.history.location || win.location, setTimeout = function (c, t) {
        return win.setTimeout(c, t)
    }, clearTimeout = function (t) {
        return win.clearTimeout(t)
    }, promise = Promise, resolver = null, nsStack = [], map = {names: [], classes: []}, xhrFactory = function (o, f) {
        for (; o.length;) try {
            return f = o.shift(), f(), f
        } catch (e) {
        }
        return function () {
            throw new Error
        }
    }([function () {
        return new XMLHttpRequest
    }, function () {
        return new ActiveXObject("Msxml2.XMLHTTP")
    }, function () {
        return new ActiveXObject("Msxml3.XMLHTTP")
    }, function () {
        return new ActiveXObject("Microsoft.XMLHTTP")
    }]), xdrFactory = function () {
        try {
            if ("withCredentials" in new XMLHttpRequest) return function () {
                return new XMLHttpRequest
            };
            if (win.XDomainRequest !== undefined) return function () {
                return new win.XDomainRequest
            }
        } catch (err) {
        }
        return function () {
            throw new Error
        }
    }();
    return api = {
        lookup: lookup,
        lookupClass: lookupClass,
        invoke: invoke,
        load: load,
        extend: extend,
        mixin: mixin,
        register: register,
        __ns: __ns
    }
}();
_context.invoke("Utils", function (undefined) {
    var Strings = {
        applyModifiers: function (s) {
            for (var a, m, f = Array.prototype.slice.call(arguments, 1), i = 0; i < f.length; i++) a = f[i].split(":"), m = a.shift(), a.unshift(s), s = Strings[m].apply(Strings, a);
            return s
        }, toString: function (s) {
            return s === undefined ? "undefined" : "string" == typeof s ? s : s.toString !== undefined ? s.toString() : Object.prototype.toString.call(s)
        }, sprintf: function (s) {
            return Strings.vsprintf(s, Array.prototype.slice.call(arguments, 1))
        }, vsprintf: function (s, args) {
            var n = 0;
            return s.replace(/%(?:(\d+)\$)?(\.\d+|\[.*?:.*?\])?([idsfa%])/g, function (m, a, p, f) {
                if ("%" === f) return f;
                if (a = a ? parseInt(a) - 1 : n++, args[a] === undefined) throw new Error("Missing parameter #" + (a + 1));
                switch (a = args[a], f) {
                    case"s":
                        return Strings.toString(a);
                    case"i":
                    case"d":
                        return parseInt(a);
                    case"f":
                        return a = parseFloat(a), p && p.match(/^\.\d+$/) && (a = a.toFixed(parseInt(p.substr(1)))), a;
                    case"a":
                        return p = p && p.match(/^\[.*:.*\]$/) ? p.substr(1, p.length - 2).split(":") : [", ", ", "], 0 === a.length ? "" : a.slice(0, -1).join(p[0]) + (a.length > 1 ? p[1] : "") + a[a.length - 1]
                }
                return m
            })
        }, webalize: function (s, chars, ws) {
            return ws && (s = s.replace(/\s+/g, "_")), s = s.replace(new RegExp("[^_A-Za-zÀ-ſ" + Strings.escapeRegex(chars || "").replace(/\\-/g, "-") + "]+", "g"), "-"), Strings.trim(s, "_-")
        }, escapeRegex: function (s) {
            return s.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")
        }, split: function (s, re, offsetCapture, noEmpty, delimCapture) {
            re = re instanceof RegExp ? new RegExp(re.source, [re.ignoreCase ? "i" : "", re.multiline ? "m" : "", "g"].filter(function (v) {
                return !!v
            }).join("")) : new RegExp(re, "g");
            var r = [], len = 0;
            return s = s.replace(re, function (m, p, ofs) {
                return ofs = arguments[arguments.length - 2], p = s.substring(len, ofs), (p.length && !p.match(/^[\t ]+$/) || !noEmpty) && r.push(offsetCapture ? [p, len] : s.substring(len, ofs)), delimCapture && (m.length && !m.match(/^[\t ]+$/) || !noEmpty) && r.push(offsetCapture ? [m, ofs] : m), len = ofs + m.length, m
            }), (len < s.length || !noEmpty) && (s = s.substring(len), (!noEmpty || s.length && !s.match(/^[\t ]+$/)) && r.push(offsetCapture ? [s, len] : s)), r
        }, trim: function (s, c) {
            return Strings._trim(s, c, !0, !0)
        }, trimLeft: function (s, c) {
            return Strings._trim(s, c, !0, !1)
        }, trimRight: function (s, c) {
            return Strings._trim(s, c, !1, !0)
        }, _trim: function (s, c, l, r) {
            c || (c = " \t\n\r\0\x0BÂ ");
            var re = [];
            return c = "[" + Strings.escapeRegex(c) + "]+", l && re.push("^", c), l && r && re.push("|"), r && re.push(c, "$"), s.replace(new RegExp(re.join(""), "ig"), "")
        }, firstUpper: function (s) {
            return s.substr(0, 1).toUpperCase() + s.substr(1)
        }, compare: function (a, b, len) {
            return "string" == typeof a && "string" == typeof b && (len || (len = Math.min(a.length, b.length)), a.substr(0, len).toLowerCase() === b.substr(0, len).toLowerCase())
        }, contains: function (h, n) {
            return h.indexOf(n) !== -1
        }, isNumeric: function (s) {
            return "[object Array]" !== Object.prototype.toString.call(s) && s - parseFloat(s) + 1 >= 0
        }, escapeHtml: function (s) {
            return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;")
        }, nl2br: function (s, collapse) {
            return s.replace(collapse ? /\n+/g : /\n/g, "<br />")
        }, random: function (len, chars) {
            chars = (chars || "a-z0-9").replace(/.-./g, function (m, a, b) {
                a = m.charCodeAt(0), b = m.charCodeAt(2);
                for (var n = Math.abs(b - a), c = new Array(n), o = Math.min(a, b), i = 0; i <= n; i++) c[i] = o + i;
                return String.fromCharCode.apply(null, c)
            }), len || (len = 8);
            var i, s = new Array(len), n = chars.length - 1;
            for (i = 0; i < len; i++) s[i] = chars[Math.round(Math.random() * n)];
            return s.join("")
        }
    };
    _context.register(Strings, "Strings")
}), _context.invoke("Utils", function (undefined) {
    var Arrays = {
        isArrayLike: function (a) {
            return "object" == typeof a && a.length !== undefined
        }, shuffle: function (a) {
            for (var t, i, c = a.length; c--;) i = Math.random() * c | 0, t = a[c], a[c] = a[i], a[i] = t;
            return a
        }, createFrom: function (a, s, e) {
            if (a.length === undefined) throw new Error("Invalid argument, only array-like objects can be supplied");
            return Array.prototype.slice.call(a, s || 0, e || a.length)
        }, getKeys: function (a) {
            var k, keys = [];
            if (Array.isArray(a)) for (k = 0; k < a.length; k++) keys.push(k); else for (k in a) keys.push(k);
            return keys
        }, filterKeys: function () {
            var a, i, rem, args = Arrays.createFrom(arguments), t = args.shift(), r = {};
            for (rem = function (k) {
                r[k] === undefined && (r[k] = t[k], delete t[k])
            }; args.length;) if (a = args.shift(), "object" == typeof a) if (a instanceof Array) for (i = 0; i < a.length; i++) rem(a[i]); else for (i in a) rem(i); else rem(a)
        }, getValues: function (a) {
            var k, arr = [];
            for (k in a) arr.push(a[k]);
            return arr
        }, merge: function () {
            var b, i, args = Arrays.createFrom(arguments), a = args.shift(), r = !1;
            for ("boolean" == typeof a && (r = a, a = args.shift()), a || (a = []); args.length;) if (b = args.shift(), b instanceof Array) for (i = 0; i < b.length; i++) r && "object" == typeof b[i] && "[object Object]" === Object.prototype.toString.call(b[i]) ? a.push(Arrays.mergeTree(r, {}, b[i])) : a.push(b[i]);
            return a
        }, mergeTree: function () {
            var r = !1, args = Arrays.createFrom(arguments), ofs = 1, t = args.shift(), props = [];
            for ("boolean" == typeof t && (r = t, t = args.shift(), ofs = 2); args.length;) {
                var p, a, i, o = args.pop();
                if ("object" == typeof o && null !== o) {
                    t || (t = {});
                    for (p in o) if (o.hasOwnProperty(p) && props.indexOf(p) === -1) {
                        if ("object" == typeof o[p]) if (r) if (o[p] instanceof Array) {
                            for (a = [r, t[p] || null], i = ofs; i < arguments.length; i++) a.push(arguments[i][p] || null);
                            t[p] = Arrays.merge.apply(this, a)
                        } else {
                            for (a = [r, null], i = ofs; i < arguments.length; i++) a.push(arguments[i] ? arguments[i][p] || null : null);
                            t[p] = Arrays.mergeTree.apply(this, a) || t[p]
                        } else t[p] = t[p] === undefined ? o[p] : null === o[p] ? t[p] : o[p]; else t[p] = o[p];
                        props.push(p)
                    }
                }
            }
            return t
        }, walk: function (r, a, f) {
            "boolean" != typeof r && (f = a, a = r, r = !1);
            var i, p = function (k, v) {
                r && (v instanceof Array || v instanceof Object) ? Arrays.walk(r, v, f) : f.call(v, k, v)
            };
            if (a instanceof Array) for (i = 0; i < a.length; i++) p(i, a[i]); else if (a instanceof Object) for (i in a) p(i, a[i]); else p(null, a)
        }
    };
    _context.register(Arrays, "Arrays")
}), _context.invoke("Utils", function (Arrays, undefined) {
    var HashMap = _context.extend(function (src) {
        this._ = {keys: [], values: [], nonNumeric: 0, nextNumeric: 0}, src && this.merge(src)
    }, {
        STATIC: {
            from: function (data, keys) {
                if (!keys) return data instanceof HashMap ? data.clone() : new HashMap(data);
                if (!Array.isArray(keys)) throw new Error("Invalid argument supplied to HashMap.from(): the second argument must be an array");
                var i, k, map = new HashMap, n = keys.length, arr = Array.isArray(data);
                for (i = 0; i < n; i++) k = arr ? i : keys[i], data[k] !== undefined && map.set(keys[i], data[k]);
                return map
            }
        }, length: 0, isList: function () {
            return 0 === this._.nonNumeric
        }, clone: function (deep) {
            var o = new HashMap;
            return o._.keys = this._.keys.slice(), o._.nextNumeric = this._.nextNumeric, o.length = this.length, deep ? o._.values = this._.values.map(function (v) {
                return v instanceof HashMap ? v.clone(deep) : v
            }) : o._.values = this._.values.slice(), o
        }, merge: function (src) {
            if (src instanceof HashMap || Array.isArray(src)) src.forEach(function (value, key) {
                this.set(key, value)
            }, this); else {
                if ("object" != typeof src || null === src) throw new TypeError("HashMap.merge() expects the first argument to be an array or an object, " + typeof src + " given");
                for (var k in src) src.hasOwnProperty(k) && this.set(k, src[k])
            }
            return this
        }, append: function (src) {
            return src instanceof HashMap || Array.isArray(src) ? src.forEach(function (value, key) {
                "number" == typeof key ? this.push(value) : this.set(key, value)
            }, this) : this.merge(src), this
        }, push: function (value) {
            for (var i = 0; i < arguments.length; i++) this._.keys.push(this._.nextNumeric), this._.values.push(arguments[i]), this._.nextNumeric++, this.length++;
            return this
        }, pop: function () {
            if (!this.length) return null;
            var k = this._.keys.pop();
            return "number" == typeof k ? k + 1 === this._.nextNumeric && this._.nextNumeric-- : this._.nonNumeric--, this.length--, this._.values.pop()
        }, shift: function () {
            return this.length ? ("number" == typeof this._.keys[0] ? (this._.nextNumeric--, this._shiftKeys(1, this.length, -1)) : this._.nonNumeric--, this.length--, this._.keys.shift(), this._.values.shift()) : null
        }, unshift: function (value) {
            for (var values = Arrays.createFrom(arguments), n = values.length, i = 0, keys = new Array(n); i < n;) keys[i] = i++;
            return keys.unshift(0, 0), values.unshift(0, 0), this._shiftKeys(0, this.length, n), this._.keys.splice.apply(this._.keys, keys), this._.values.splice.apply(this._.values, values), this._.nextNumeric += n, this.length += n, this
        }, slice: function (from, to) {
            from === undefined && (from = 0), from < 0 && (from += this.length), to === undefined && (to = this.length), to < 0 && (to += this.length);
            var o = new HashMap;
            return o._.keys = this._.keys.slice(from, to).map(function (k) {
                return "number" == typeof k ? (k = o._.nextNumeric, o._.nextNumeric++, k) : (o._.nonNumeric++, k)
            }), o._.values = this._.values.slice(from, to), o.length = o._.keys.length, o
        }, splice: function (from, remove) {
            var removed, i, values = Arrays.createFrom(arguments), keys = values.slice().map(function () {
                return -1
            });
            for (keys[0] = values[0], keys[1] = values[1], this._.keys.splice.apply(this._.keys, keys), removed = this._.values.splice.apply(this._.values, values), this.length = this._.keys.length, this._.nextNumeric = 0, this._.nonNumeric = 0, i = 0; i < this.length; i++) "number" == typeof this._.keys[i] ? (this._.keys[i] = this._.nextNumeric, this._.nextNumeric++) : this._.nonNumeric++;
            return removed
        }, set: function (key, value) {
            var i = this._.keys.indexOf(key);
            return i === -1 ? (this._.keys.push(key), this._.values.push(value), this.length++, "number" == typeof key ? key >= this._.nextNumeric && (this._.nextNumeric = key + 1) : this._.nonNumeric++) : this._.values[i] = value, this
        }, get: function (key, need) {
            var i = this._.keys.indexOf(key);
            if (i > -1) return this._.values[i];
            if (need) throw new RangeError("Key " + key + " not present in HashMap");
            return null
        }, has: function (key) {
            var index = this._.keys.indexOf(key);
            return index > -1 && this._.values[index] !== undefined
        }, remove: function (key, strict) {
            var index = this._.keys.indexOf(key);
            if (index > -1) this._.keys.splice(index, 1), this._.values.slice(index, 1), this.length--, "number" == typeof key ? key + 1 === this._.nextNumeric && this._.nextNumeric-- : this._.nonNumeric--; else if (strict) throw new RangeError("Key " + key + " not present in HashMap");
            return this
        }, forEach: function (callback, thisArg) {
            for (var i = 0; i < this.length; i++) callback.call(thisArg || null, this._.values[i], this._.keys[i], this);
            return this
        }, map: function (callback, recursive, thisArg) {
            return this.clone(recursive).walk(callback, recursive, thisArg)
        }, walk: function (callback, recursive, thisArg) {
            for (var i = 0; i < this.length; i++) recursive && this._.values[i] instanceof HashMap ? this._.values[i].walk(callback, recursive, thisArg) : this._.values[i] = callback.call(thisArg || null, this._.values[i], this._.keys[i], this);
            return this
        }, find: function (predicate, thisArg) {
            var i = this._find(predicate, thisArg, !0);
            return i === !1 ? null : this._.values[i]
        }, findKey: function (predicate, thisArg) {
            var i = this._find(predicate, thisArg, !0);
            return i === !1 ? null : this._.keys[i]
        }, some: function (predicate, thisArg) {
            return this._find(predicate, thisArg, !0) !== !1
        }, all: function (predicate, thisArg) {
            return this._find(predicate, thisArg, !1) === !1
        }, filter: function (predicate, thisArg) {
            var i, o = new HashMap;
            for (i = 0; i < this.length; i++) predicate.call(thisArg || null, this._.values[i], this._.keys[i], this) && ("number" == typeof this._.keys[i] ? o.push(this._.values[i]) : o.set(this._.keys[i], this._.values[i]));
            return o
        }, exportData: function () {
            if (this.isList()) return this.getValues().map(function (v) {
                return v instanceof HashMap ? v.exportData() : v
            });
            for (var i = 0, r = {}; i < this.length; i++) this._.values[i] instanceof HashMap ? r[this._.keys[i]] = this._.values[i].exportData() : r[this._.keys[i]] = this._.values[i];
            return r
        }, getKeys: function () {
            return this._.keys.slice()
        }, getValues: function () {
            return this._.values.slice()
        }, _shiftKeys: function (from, to, diff) {
            for (; from < to;) "number" == typeof this._.keys[from] && (this._.keys[from] += diff), from++
        }, _find: function (predicate, thisArg, expect) {
            for (var i = 0; i < this.length; i++) if (predicate.call(thisArg || null, this._.values[i], this._.keys[i], this) === expect) return i;
            return !1
        }
    });
    _context.register(HashMap, "HashMap")
}), _context.invoke("Utils", function (Strings, undefined) {
    var location = window.history.location || window.location, Url = function (s) {
        var cur = location.href.match(Url.PARSER_REGEXP),
            src = null === s || "" === s || s === undefined ? cur : s.match(Url.PARSER_REGEXP), noHost = !src[4],
            path = src[6] || "";
        noHost && "/" !== path.charAt(0) && (path = path.length ? Url.getDirName(cur[6] || "") + "/" + path.replace(/^\.\//, "") : cur[6]), this._ = {
            protocol: src[1] || cur[1] || "",
            username: (noHost ? src[2] || cur[2] : src[2]) || "",
            password: (noHost ? src[3] || cur[3] : src[3]) || "",
            hostname: src[4] || cur[4] || "",
            port: (noHost ? src[5] || cur[5] : src[5]) || "",
            path: path,
            params: Url.parseQuery((noHost && !src[6] ? src[7] || cur[7] : src[7]) || ""),
            hash: (!noHost || src[6] || src[7] ? src[8] : src[8] || cur[8]) || ""
        }
    };
    Url.prototype.getProtocol = function () {
        return this._.protocol
    }, Url.prototype.getUsername = function () {
        return this._.username
    }, Url.prototype.getPassword = function () {
        return this._.password
    }, Url.prototype.getHostname = function () {
        return this._.hostname
    }, Url.prototype.getPort = function () {
        return this._.port
    }, Url.prototype.getAuthority = function () {
        var a = "";
        return this._.username && (a += this._.password ? this._.username + ":" + this._.password + "@" : this._.username + "@"), a += this._.hostname, this._.port && (a += ":" + this._.port), a
    }, Url.prototype.getOrigin = function () {
        return this._.protocol + "//" + this._.hostname + (this._.port ? ":" + this._.port : "")
    }, Url.prototype.getPath = function () {
        return this._.path
    }, Url.prototype.getQuery = function () {
        var q = Url.buildQuery(this._.params);
        return q.length ? "?" + q : ""
    }, Url.prototype.getParam = function (n) {
        return this._.params[n]
    }, Url.prototype.hasParam = function (n) {
        return this._.params[n] !== undefined
    }, Url.prototype.getParams = function () {
        return this._.params
    }, Url.prototype.getHash = function () {
        return this._.hash
    }, Url.prototype.setProtocol = function (protocol) {
        return this._.protocol = protocol ? Strings.trimRight(protocol, ":") + ":" : "", this
    }, Url.prototype.setUsername = function (username) {
        return this._.username = username, this
    }, Url.prototype.setPassword = function (password) {
        return this._.password = password, this
    }, Url.prototype.setHostname = function (hostname) {
        return this._.hostname = hostname, this
    }, Url.prototype.setPort = function (port) {
        return this._.port = port, this
    }, Url.prototype.setPath = function (path) {
        return this._.path = path ? "/" + Strings.trimLeft(path, "/") : "", this
    }, Url.prototype.setQuery = function (query) {
        return this._.params = Url.parseQuery(query), this
    }, Url.prototype.setParam = function (n, v) {
        return this._.params[n] = v, this
    }, Url.prototype.addParams = function (p) {
        Array.isArray(p) && p.length && "name" in p[0] && "value" in p[0] && (p = Url.parseQuery(Url.buildQuery(p, !0)));
        for (var k in p) p[k] !== undefined && (this._.params[k] = p[k]);
        return this
    }, Url.prototype.getParams = function () {
        return this._.params
    }, Url.prototype.setParams = function (p) {
        return this._.params = {}, this.addParams(p), this
    }, Url.prototype.removeParam = function (n) {
        return delete this._.params[n], this
    }, Url.prototype.setHash = function (hash) {
        return this._.hash = hash ? "#" + Strings.trimLeft(hash, "#") : "", this
    }, Url.prototype.toAbsolute = function () {
        return this._.protocol + "//" + this.getAuthority() + this._.path + this.getQuery() + this._.hash
    }, Url.prototype.toLocal = function () {
        return this._.path + this.getQuery() + this._.hash
    }, Url.prototype.toRelative = function (to) {
        if (to = Url.from(to || location.href), to.getProtocol() !== this.getProtocol()) return this.toAbsolute();
        if (to.getAuthority() !== this.getAuthority()) return "//" + this.getAuthority() + this.getPath() + this.getQuery() + this.getHash();
        if (to.getPath() !== this.getPath()) return Url.getRelativePath(to.getPath(), this.getPath()) + this.getQuery() + this.getHash();
        var qto = to.getQuery(), qthis = this.getQuery();
        return qto !== qthis ? qthis + this.getHash() : to.getHash() === this.getHash() ? "" : this.getHash()
    }, Url.prototype.toString = function () {
        return this.toAbsolute()
    }, Url.prototype.isLocal = function () {
        return this.compare(Url.fromCurrent()) < Url.PART.PORT
    }, Url.prototype.compare = function (to) {
        to instanceof Url || (to = Url.from(to));
        var r = 0;
        return this.getProtocol() !== to.getProtocol() && (r |= Url.PART.PROTOCOL), this.getUsername() !== to.getUsername() && (r |= Url.PART.USERNAME), this.getPassword() !== to.getPassword() && (r |= Url.PART.PASSWORD), this.getHostname() !== to.getHostname() && (r |= Url.PART.HOSTNAME), this.getPort() !== to.getPort() && (r |= Url.PART.PORT), this.getPath() !== to.getPath() && (r |= Url.PART.PATH), this.getQuery() !== to.getQuery() && (r |= Url.PART.QUERY), this.getHash() !== to.getHash() && (r |= Url.PART.HASH), r
    }, Url.PARSER_REGEXP = /^(?:([^:\/]+:)?\/\/(?:([^\/@]+?)(?::([^\/@]+))?@)?(?:([^\/]+?)(?::(\d+))?(?=\/|$))?)?(.*?)(\?.*?)?(#.*)?$/, Url.PART = {
        PROTOCOL: 128,
        USERNAME: 64,
        PASSWORD: 32,
        HOSTNAME: 16,
        PORT: 8,
        PATH: 4,
        QUERY: 2,
        HASH: 1
    }, Url.from = function (s) {
        return new Url(s instanceof Url ? s.toAbsolute() : "string" == typeof s || null === s || s === undefined ? s : Strings.toString(s))
    }, Url.fromCurrent = function () {
        return new Url
    }, Url.getDirName = function (path) {
        return path.replace(/(^|\/)[^\/]*$/, "")
    }, Url.getRelativePath = function (from, to) {
        if (from = Strings.trimLeft(from, "/").split("/"), from.pop(), !to.match(/^\//)) return to.replace(/^\.\//, "");
        to = Strings.trimLeft(to, "/").split("/");
        for (var f, t, e = 0, o = [], n = Math.min(from.length, to.length); e < n && from[e] === to[e]; e++) ;
        for (f = e; f < from.length; f++) o.push("..");
        for (t = e; t < to.length; t++) o.push(to[t]);
        return o.join("/")
    }, Url.buildQuery = function (data, pairs) {
        function en(v) {
            return encodeURIComponent(v).replace(/%20/g, "+")
        }

        function val(v) {
            return v === undefined ? null : "boolean" == typeof v ? v ? 1 : 0 : en("" + v)
        }

        function flatten(a, n) {
            var i, r = [];
            if (Array.isArray(a)) for (i = 0; i < a.length; i++) r.push(en(n + "[]") + "=" + val(a[i])); else for (i in a) "object" == typeof a[i] ? r.push(flatten(a[i], n + "[" + i + "]")) : r.push(en(n + "[" + i + "]") + "=" + val(a[i]));
            return r.length ? r.filter(function (v) {
                return null !== v
            }).join("&") : null
        }

        var n, q = [];
        for (n in data) null !== data[n] && data[n] !== undefined && (pairs ? q.push(en(data[n].name) + "=" + val(data[n].value)) : "object" == typeof data[n] ? q.push(flatten(data[n], n)) : q.push(en(n) + "=" + val(data[n])));
        return q.filter(function (v) {
            return null !== v
        }).join("&")
    }, Url.parseQuery = function (s) {
        function dec(v) {
            return decodeURIComponent(v.replace(/\+/g, " "))
        }

        function convertType(v) {
            var c;
            return v.match(/^(?:[1-9]\d*|0)$/) && (c = parseInt(v)) + "" === v ? c : v.match(/^\d*\.\d+$/) && (c = parseFloat(v)) + "" === v ? c : v
        }

        if (s.match(/^\??$/)) return {};
        s = Strings.trimLeft(s, "?").split("&");
        var c, d, k, i, m, n, v, p = {}, a = !1;
        for (i = 0; i < s.length; i++) if (m = s[i].split("="), n = dec(m.shift()), v = convertType(dec(m.join("="))), n.indexOf("[") !== -1) {
            for (n = n.replace(/\]/g, ""), d = n.split("["), c = p, a = !1, n.match(/\[$/) && (d.pop(), a = !0), n = d.pop(); d.length;) k = d.shift(), c[k] === undefined && (c[k] = {}), c = c[k];
            a ? c[n] === undefined ? c[n] = [v] : c[n].push(v) : c[n] = v
        } else p[n] = v;
        return p
    }, _context.register(Url, "Url")
}), _context.invoke("Utils", function (Arrays, Strings, undefined) {
    function map(args, callback) {
        if (args = Arrays.createFrom(args), Array.isArray(args[0])) {
            for (var i = 0, elems = args[0], ret = []; i < elems.length; i++) args[0] = getElem(elems[i]), args[0] ? ret.push(callback.apply(null, args)) : ret.push(args[0]);
            return ret
        }
        return args[0] = getElem(args[0]), args[0] ? callback.apply(null, args) : args[0]
    }

    function getElem(elem) {
        return (Array.isArray(elem) || elem instanceof HTMLCollection || elem instanceof NodeList) && (elem = elem[0]), "string" == typeof elem ? DOM.getById(elem) : elem
    }

    function getPrefixed(elem, prop) {
        if (elem = getElem(elem), prop in elem.style) return prop;
        var i, p = prop.charAt(0).toUpperCase() + prop.substr(1),
            variants = ["webkit" + p, "moz" + p, "o" + p, "ms" + p];
        for (i = 0; i < variants.length; i++) if (variants[i] in elem.style) return variants[i];
        return prop
    }

    function parseData(value) {
        if (!value) return null;
        try {
            return JSON.parse(value)
        } catch (e) {
            return value
        }
    }

    "function" != typeof window.CustomEvent && (window.CustomEvent = function (event, params) {
        params = params || {bubbles: !1, cancelable: !1, detail: undefined};
        var evt = document.createEvent("CustomEvent");
        return evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail), evt
    }, window.CustomEvent.prototype = window.Event.prototype);
    var knownEventModules = {
        MouseEvent: {
            create: function (type, params) {
                return params.view || (params.view = window), new MouseEvent(type, params)
            }, init: function (event, type, params) {
                event.initMouseEvent(type, params.bubbles, params.cancelable, params.view || window, params.detail || 1, params.screenX || 0, params.screenY || 0, params.clientX || 0, params.clientY || 0, params.ctrlKey || !1, params.altKey || !1, params.shiftKey || !1, params.metaKey || !1, params.button || 1, params.relatedTarget)
            }
        }, KeyboardEvent: {
            create: function (type, params) {
                return new KeyboardEvent(type, params)
            }, init: function (event, type, params) {
                var modifiers = [];
                params.ctrlKey && modifiers.push("Control"), params.shiftKey && modifiers.push("Shift"), params.altKey && modifiers.push("Alt"), params.metaKey && modifiers.push("Meta"), event.initKeyboardEvent(type, params.bubbles, params.cancelable, params.view || window, params.key || "", params.location || 0, modifiers.join(" "))
            }
        }, FocusEvent: {
            create: function (type, params) {
                return new FocusEvent(type, params)
            }, init: function (event, type, params) {
                event.initUIEvent(type, params.bubbles, params.cancelable, params.view || window, params.detail || 0)
            }, name: "UIEvent"
        }, HTMLEvents: {
            create: function (type, params) {
                return new Event(type, params)
            }, init: function (event, type, params) {
                event.initEvent(type, params.bubbles, params.cancelable)
            }
        }, CustomEvent: {
            create: function (type, params) {
                return new CustomEvent(type, params)
            }, init: function (event, type, params) {
                event.initCustomEvent(type, params.bubbles, params.cancelable, params.detail)
            }
        }
    }, knownEvents = {
        click: "MouseEvent",
        dblclick: "MouseEvent",
        mousedown: "MouseEvent",
        mouseenter: "MouseEvent",
        mouseleave: "MouseEvent",
        mousemove: "MouseEvent",
        mouseout: "MouseEvent",
        mouseover: "MouseEvent",
        mouseup: "MouseEvent",
        contextmenu: "MouseEvent",
        keydown: "KeyboardEvent",
        keypress: "KeyboardEvent",
        keyup: "KeyboardEvent",
        focus: "FocusEvent",
        blur: "FocusEvent",
        change: "HTMLEvents",
        submit: "HTMLEvents",
        reset: "HTMLEvents"
    }, containers = {
        caption: "table",
        colgroup: "table",
        col: "colgroup",
        thead: "table",
        tbody: "table",
        tfoot: "table",
        tr: "table",
        th: "tr",
        td: "tr",
        li: "ul",
        optgroup: "select",
        option: "select"
    }, DOM = {
        getByClassName: function (className, context) {
            return Arrays.createFrom((context || document).getElementsByClassName(className))
        }, getById: function (id) {
            return document.getElementById(id)
        }, find: function (sel, context) {
            var elems = [];
            return sel = sel.trim().split(/\s*,\s*/g), sel.forEach(function (s) {
                var m = s.match(/^#([^\s\[>+:.]+)\s+\.([^\s\[>+:]+)$/);
                if (m) return void elems.push.apply(elems, DOM.getByClassName(m[2], DOM.getById(m[1])));
                if (s.match(/^[^.#]|[\s\[>+:]/)) throw new TypeError('Invalid selector "' + s + '", only single-level .class and #id or "#id .class" are allowed');
                "#" === s.charAt(0) ? (m = DOM.getById(s.substr(1)), m && elems.push(m)) : (m = DOM.getByClassName(s.substr(1), context), elems.push.apply(elems, m))
            }), elems
        }, getChildren: function (elem) {
            return Arrays.createFrom(elem.childNodes || "").filter(function (node) {
                return 1 === node.nodeType
            })
        }, closest: function (elem, nodeName, className) {
            return map(arguments, function (elem, nodeName, className) {
                for (; elem;) {
                    if (1 === elem.nodeType && (!nodeName || elem.nodeName.toLowerCase() === nodeName) && (!className || DOM.hasClass(elem, className))) return elem;
                    elem = elem.parentNode
                }
                return null
            })
        }, create: function (elem, attrs) {
            return elem = document.createElement(elem), attrs && DOM.setAttributes(elem, attrs), elem
        }, createFromHtml: function (html) {
            var container, elems;
            return (container = html.match(/^\s*<(caption|colgroup|col|thead|tbody|tfoot|tr|th|td|li|optgroup|option)[\s>]/i)) && (container = containers[container[1].toLowerCase()]), container = DOM.create(container || "div"), DOM.html(container, html), elems = DOM.getChildren(container),
                elems.forEach(function (e) {
                    container.removeChild(e)
                }), container = null, elems.length > 1 ? elems : elems[0]
        }, setAttributes: function (elem, attrs) {
            return map([elem], function (elem) {
                for (var a in attrs) attrs.hasOwnProperty(a) && elem.setAttribute(a, attrs[a]);
                return elem
            })
        }, setStyle: function (elem, prop, value, prefix) {
            if (prop && "object" == typeof prop) {
                prefix = value, value = prop;
                for (prop in value) value.hasOwnProperty(prop) && DOM.setStyle(elem, prop, value[prop], prefix);
                return elem
            }
            return prefix !== !1 && (prop = getPrefixed(elem, prop)), map([elem], function (elem) {
                elem.style[prop] = value
            })
        }, getStyle: function (elem, props, prefix) {
            Array.isArray(props) || (props = props.split(/\s+/g));
            var prefixed = props;
            return prefix !== !1 && (prefixed = props.map(function (prop) {
                return getPrefixed(elem, prop)
            })), map([elem], function (elem) {
                var style = window.getComputedStyle(elem);
                if (1 === props.length) return style[prefixed[0]];
                var res = {};
                return props.forEach(function (prop, i) {
                    res[prop] = style[prefixed[i]]
                }), res
            })
        }, getStyleFloat: function (elem, props, prefix) {
            function normalizeValue(v) {
                var m = refloat.exec(v);
                return m && (v = parseFloat(m[1]), "s" === m[2] && (v *= 1e3)), v
            }

            function stylePropsToFloat(style) {
                return 1 === props.length ? normalizeValue(style) : (props.forEach(function (prop) {
                    style[prop] = normalizeValue(style[prop])
                }), style)
            }

            Array.isArray(props) || (props = props.split(/\s+/g));
            var style = DOM.getStyle(elem, props, prefix), refloat = /^(\d+|\d*\.\d+)(px|m?s)?$/;
            return Array.isArray(style) ? style.map(stylePropsToFloat) : stylePropsToFloat(style)
        }, html: function (elem, html) {
            return map([elem], function (elem) {
                elem.innerHTML = html, Arrays.createFrom(elem.getElementsByTagName("script")).forEach(function (elem) {
                    var type = elem.type ? elem.type.toLowerCase() : null;
                    if (!type || "text/javascript" === type || "application/javascript" === type) {
                        var i, script, load = elem.hasAttribute("src"),
                            src = load ? elem.src : elem.text || elem.textContent || elem.innerHTML || "", attrs = {};
                        for (i = 0; i < elem.attributes.length; i++) "src" !== elem.attributes.item(i).name && (attrs[elem.attributes.item(i).name] = elem.attributes.item(i).value);
                        if (script = DOM.create("script", attrs), load) script.src = src; else try {
                            script.appendChild(document.createTextNode(src))
                        } catch (e) {
                            script.text = src
                        }
                        elem.parentNode.insertBefore(script, elem), elem.parentNode.removeChild(elem)
                    }
                })
            })
        }, empty: function (elem) {
            return map(arguments, function (elem) {
                for (; elem.firstChild;) elem.removeChild(elem.firstChild)
            })
        }, append: function (elem, children) {
            return elem = getElem(elem), children = Array.isArray(children) ? children : Arrays.createFrom(arguments, 1), children.forEach(function (child) {
                elem.appendChild(child)
            }), elem
        }, prepend: function (elem, children) {
            elem = getElem(elem), children = Array.isArray(children) ? children : Arrays.createFrom(arguments, 1);
            var first = elem.firstChild;
            return children.forEach(function (child) {
                elem.insertBefore(child, first)
            }), elem
        }, insertBefore: function (before, elem) {
            var parent, elems = Array.isArray(elem) ? elem : Arrays.createFrom(arguments, 1);
            return before = getElem(before), parent = before.parentNode, elems.forEach(function (elem) {
                parent.insertBefore(elem, before)
            }), before
        }, contains: function (a, b) {
            var adown = 9 === a.nodeType ? a.documentElement : a, bup = b && b.parentNode;
            return a === bup || !(!bup || 1 !== bup.nodeType || !(adown.contains ? adown.contains(bup) : a.compareDocumentPosition && 16 & a.compareDocumentPosition(bup)))
        }, addListener: function (elem, evt, listener, capture) {
            return map(arguments, function (elem, evt, listener, capture) {
                return elem.addEventListener(evt, listener, !!capture), elem
            })
        }, removeListener: function (elem, evt, listener, capture) {
            return map(arguments, function (elem, evt, listener, capture) {
                return elem.removeEventListener(evt, listener, !!capture), elem
            })
        }, trigger: function (elem, evt, params) {
            var event, module = knownEvents[evt] || "CustomEvent";
            params || (params = {}), "bubbles" in params || (params.bubbles = !0), "cancelable" in params || (params.cancelable = !0);
            try {
                event = knownEventModules[module].create(evt, params)
            } catch (e) {
                event = document.createEvent(knownEventModules[module].name || module), knownEventModules[module].init(event, evt, params)
            }
            return getElem(elem).dispatchEvent(event)
        }, delegate: function (sel, handler) {
            return sel = sel.trim().split(/\s*,\s*/g).map(function (s) {
                var m = s.match(/^(?:(?:#([^\s\[>+:.]+)\s+)?\.([^\s\[>+:]+)|#([^\s\[>+:.]+))$/);
                return [m[1] || m[3], m[2]]
            }), function (evt) {
                if (evt.target) {
                    var i, j, elems = [], ids = [], classes = [], found = [], elem = evt.target;
                    do elems.push(elem), ids.push(elem.id), classes.push(((elem.className || "") + "").trim().split(/\s+/g)); while (elem = elem.parentNode);
                    for (i = 0; i < elems.length; i++) for (j = 0; j < sel.length; j++) sel[j][1] && !(classes[i].indexOf(sel[j][1]) > -1) || sel[j][0] && !(sel[j][1] ? ids.indexOf(sel[j][0]) > i : ids[i] === sel[j][0]) || found.push(elems[i]);
                    for (i = 0; i < found.length; i++) handler.call(found[i], evt, found[i])
                }
            }
        }, getData: function (elem, key, def) {
            return elem = getElem(elem), key = "data-" + key, elem.hasAttribute(key) ? parseData(elem.getAttribute(key)) : def
        }, setData: function (elem, key, value) {
            return map([elem], function (elem) {
                return elem.setAttribute("data-" + key, JSON.stringify(value)), elem
            })
        }, addClass: null, removeClass: null, toggleClass: null, hasClass: null
    }, testElem = DOM.create("span"), prepare = function (args, asStr) {
        return args = Arrays.createFrom(args, 1).join(" ").trim(), asStr ? args : args.split(/\s+/g)
    };
    "classList" in testElem ? (testElem.classList.add("c1", "c2"), testElem.classList.contains("c2") ? (DOM.addClass = function (elem, classes) {
        return classes = prepare(arguments), map([elem], function (elem) {
            return elem.classList.add.apply(elem.classList, classes), elem
        })
    }, DOM.removeClass = function (elem, classes) {
        return classes = prepare(arguments), map([elem], function (elem) {
            return elem.classList.remove.apply(elem.classList, classes), elem
        })
    }) : (DOM.addClass = function (elem, classes) {
        return classes = prepare(arguments), map([elem], function (elem) {
            return classes.forEach(function (c) {
                elem.classList.add(c)
            }), elem
        })
    }, DOM.removeClass = function (elem, classes) {
        return classes = prepare(arguments), map([elem], function (elem) {
            return classes.forEach(function (c) {
                elem.classList.remove(c)
            }), elem
        })
    }), testElem.classList.toggle("c1", !0), testElem.classList.contains("c1") ? DOM.toggleClass = function (elem, classes, value) {
        return classes = classes.trim().split(/\s+/g), map([elem], function (elem) {
            return value === undefined ? classes.forEach(function (c) {
                elem.classList.toggle(c)
            }) : classes.forEach(function (c) {
                elem.classList.toggle(c, !!value)
            }), elem
        })
    } : DOM.toggleClass = function (elem, classes, value) {
        return classes = classes.trim().split(/\s+/g), map([elem], function (elem) {
            return classes.forEach(function (c) {
                value !== undefined && !value !== elem.classList.contains(c) || elem.classList.toggle(c)
            }), elem
        })
    }, DOM.hasClass = function (elem, classes) {
        elem = getElem(elem), classes = prepare(arguments);
        for (var i = 0; i < classes.length; i++) if (!elem.classList.contains(classes[i])) return !1;
        return !0
    }) : (DOM.addClass = function (elem, classes) {
        return classes = prepare(arguments, !0), map([elem], function (elem) {
            return elem.className += (elem.className ? " " : "") + classes, elem
        })
    }, DOM.removeClass = function (elem, classes) {
        return classes = prepare(arguments).map(Strings.escapeRegex), map([elem], function (elem) {
            return elem.className ? (elem.className = elem.className.replace(new RegExp("(?:(?:^|\\s+)(?:" + classes.join("|") + "))+(?=\\s+|$)", "g"), " ").trim().replace(/\s\s+/g, " "), elem) : elem
        })
    }, DOM.toggleClass = function (elem, classes, value) {
        return classes = classes.trim().split(/\s+/g), map([elem], function (elem) {
            var current = (elem.className || "").trim().split(/\s+/g);
            return classes.forEach(function (c) {
                var i = current.indexOf(c), has = i > -1;
                value === !1 || has ? value !== !0 && has && current.splice(i, 1) : current.push(c)
            }), elem.className = current.join(" "), elem
        })
    }, DOM.hasClass = function (elem, classes) {
        if (elem = getElem(elem), !elem.className) return !1;
        classes = prepare(arguments);
        for (var current = elem.className.trim().split(/\s+/g), i = 0; i < classes.length; i++) if (current.indexOf(classes[i]) === -1) return !1;
        return !0
    }), testElem = null, _context.register(DOM, "DOM")
}), _context.invoke("Utils", function (DOM) {
    var CSSTransitions = {
        support: "getComputedStyle" in window, getDuration: function (elements) {
            Array.isArray(elements) || (elements = [elements]);
            var durations = DOM.getStyle(elements, "animationDuration").concat(DOM.getStyle(elements, "transitionDuration")).map(function (d) {
                return d ? Math.max.apply(null, d.split(/\s*,\s*/g).map(function (v) {
                    return v = v.match(/^((?:\d*\.)?\d+)(m?s)$/), v ? parseFloat(v[1]) * ("ms" === v[2] ? 1 : 1e3) : 0
                })) : 0
            });
            return durations.length ? Math.max.apply(null, durations) : 0
        }, run: function (elements, classes, forceLayout) {
            return CSSTransitions.support && (Array.isArray(elements) ? elements.length : elements) ? CSSTransitions._resolve(elements, classes, forceLayout) : Promise.resolve(elements)
        }, _resolve: function (elements, classes, forceLayout) {
            if (forceLayout) {
                window.pageXOffset
            }
            classes && classes.add && DOM.addClass(elements, classes.add), classes && classes.remove && DOM.removeClass(elements, classes.remove);
            var duration = CSSTransitions.getDuration(elements);
            return new Promise(function (fulfill) {
                window.setTimeout(function () {
                    classes && classes.add && DOM.removeClass(elements, classes.add), classes && classes.after && DOM.addClass(elements, classes.after), fulfill(elements)
                }, duration)
            })
        }
    };
    if (CSSTransitions.support) try {
        var s = DOM.create("span").style;
        CSSTransitions.support = ["transition", "WebkitTransition", "MozTransition", "msTransition", "OTransition"].some(function (prop) {
            return prop in s
        }), s = null
    } catch (e) {
    }
    _context.register(CSSTransitions, "CSSTransitions")
}), _context.invoke("Utils", function (undefined) {
    var ReflectionClass = function (c) {
        this._ = {reflectedClass: "string" == typeof c ? ReflectionClass.getClass(c) : c}
    };
    ReflectionClass.from = function (c) {
        return c instanceof ReflectionClass ? c : new ReflectionClass(c)
    }, ReflectionClass.getClass = function (name) {
        return _context.lookup(name)
    }, ReflectionClass.getClassName = function (obj, need) {
        var className = _context.lookupClass(obj);
        if (className === !1 && need) throw new Error("Unknown class");
        return className
    }, ReflectionClass.prototype.hasProperty = function (name) {
        return this._.reflectedClass.prototype[name] !== undefined && "function" != typeof this._.reflectedClass.prototype[name]
    }, ReflectionClass.prototype.hasMethod = function (name) {
        return this._.reflectedClass.prototype[name] !== undefined && "function" == typeof this._.reflectedClass.prototype[name]
    }, ReflectionClass.prototype.newInstance = function () {
        return this.newInstanceArgs(arguments)
    }, ReflectionClass.prototype.newInstanceArgs = function (args) {
        var inst, ret, tmp = function () {
        };
        return tmp.prototype = this._.reflectedClass.prototype, inst = new tmp, ret = this._.reflectedClass.apply(inst, args), Object(ret) === ret ? ret : inst
    }, _context.register(ReflectionClass, "ReflectionClass")
}), _context.invoke("Utils", function (Arrays, undefined) {
    var ReflectionFunction = function (f) {
        this._ = {reflectedFunction: f, argsList: null, name: null};
        var parts = f.toString().match(/^\s*function(?:\s*|\s+([^\(]+?)\s*)\(\s*([\s\S]*?)\s*\)/i);
        this._.name = parts[1] || null, this._.argsList = parts[2] ? parts[2].replace(/\/\*\*?[\s\S]*?\*\//g, "").trim().split(/\s*,\s*/) : []
    };
    ReflectionFunction.from = function (f) {
        return f instanceof ReflectionFunction ? f : new ReflectionFunction(f)
    }, ReflectionFunction.prototype.getName = function () {
        return this._.name
    }, ReflectionFunction.prototype.getArgs = function () {
        return this._.argsList
    }, ReflectionFunction.prototype.invoke = function (context) {
        var args = Arrays.createFrom(arguments);
        return args.shift(), this._.reflectedFunction.apply(context, args)
    }, ReflectionFunction.prototype.invokeArgs = function (context, args) {
        for (var list = [], i = 0; i < this._.argsList.length; i++) {
            if (args[this._.argsList[i]] === undefined) throw new Error('Parameter "' + this._.argsList[i] + '" was not provided in argument list');
            list.push(args[this._.argsList[i]])
        }
        return this._.reflectedFunction.apply(context, list)
    }, _context.register(ReflectionFunction, "ReflectionFunction")
}), _context.invoke("Nittro", function () {
    function prepare(self, need) {
        if (!self._) {
            if (need === !1) return !1;
            self._ = {}
        }
        if (!self._.eventEmitter) {
            if (need === !1) return !1;
            self._.eventEmitter = {listeners: {}, defaultListeners: {}, namespaces: []}
        }
    }

    function prepareNamespaces(emitter, namespaces) {
        return namespaces.map(function (ns) {
            var i = emitter.namespaces.indexOf(ns);
            return i > -1 ? i : (i = emitter.namespaces.length, emitter.namespaces.push(ns), i)
        })
    }

    function hasCommonElement(a, b) {
        for (var i = 0, j = 0; i < a.length && j < b.length;) if (a[i] < b[j]) i++; else {
            if (!(a[i] > b[j])) return !0;
            j++
        }
        return !1
    }

    function process(emitter, evt, op, arg1, arg2) {
        evt = (evt || "").replace(/^\s+|\s+$/g, "").split(/\s+/g), evt.forEach(function (e) {
            var dflt = e.split(/:/), ns = dflt[0].split(/\./g);
            e = ns.shift(), ns = prepareNamespaces(emitter, ns), ns.sort(), op(emitter, e, ns, "default" === dflt[1], arg1, arg2)
        })
    }

    function add(emitter, evt, ns, dflt, handler, mode) {
        if (!evt) throw new TypeError("No event specified");
        if (dflt) {
            if (0 !== mode || ns.length) throw new TypeError("Default event handlers don't support namespaces and one()/first()");
            if (emitter.defaultListeners.hasOwnProperty(evt)) throw new TypeError("Event '" + evt + "' already has a default listener");
            return void(emitter.defaultListeners[evt] = handler)
        }
        2 === mode && ns.unshift(emitter.namespaces.length), emitter.listeners[evt] || (emitter.listeners[evt] = []), emitter.listeners[evt].push({
            handler: handler,
            namespaces: ns,
            mode: mode
        })
    }

    function remove(emitter, evt, ns, dflt, handler) {
        if (evt) return dflt ? void(!emitter.defaultListeners.hasOwnProperty(evt) || handler && emitter.defaultListeners[evt] !== handler || delete emitter.defaultListeners[evt]) : void(emitter.listeners[evt] && (ns.length ? emitter.listeners[evt] = emitter.listeners[evt].filter(function (listener) {
            return !(!handler || listener.handler === handler) || (!listener.namespaces.length || !hasCommonElement(listener.namespaces, ns))
        }) : handler ? emitter.listeners[evt] = emitter.listeners[evt].filter(function (listener) {
            return listener.handler !== handler
        }) : (emitter.listeners.hasOwnProperty(evt) && delete emitter.listeners[evt], emitter.defaultListeners.hasOwnProperty(evt) && delete emitter.defaultListeners[evt])));
        var listeners = dflt ? emitter.defaultListeners : emitter.listeners;
        for (evt in listeners) listeners.hasOwnProperty(evt) && remove(emitter, evt, ns, dflt, handler)
    }

    function trigger(self, evt, data) {
        var e, _ = self._.eventEmitter;
        return "object" == typeof evt ? (e = evt, evt = e.type) : e = new NittroEvent(self, evt, data), _.listeners.hasOwnProperty(evt) && _.listeners[evt].slice().forEach(function (listener) {
            1 === listener.mode ? remove(_, evt, [], !1, listener.handler) : 2 === listener.mode && remove(_, "", [listener.namespaces[0]], !1), listener.handler.call(self, e)
        }), e.isAsync() ? e.then(function () {
            triggerDefault(self, _, evt, e)
        }, function () {
        }) : triggerDefault(self, _, evt, e), e
    }

    function triggerDefault(self, _, evt, e) {
        !e.isDefaultPrevented() && _.defaultListeners.hasOwnProperty(evt) && _.defaultListeners[evt].call(self, e)
    }

    var NittroEventEmitter = {
        on: function (evt, handler) {
            return prepare(this), process(this._.eventEmitter, evt, add, handler, 0), this
        }, one: function (evt, handler) {
            return prepare(this), process(this._.eventEmitter, evt, add, handler, 1), this
        }, first: function (evt, handler) {
            return prepare(this), process(this._.eventEmitter, evt, add, handler, 2), this._.eventEmitter.namespaces.push(null), this
        }, off: function (evt, handler) {
            return prepare(this, !1) === !1 ? this : (process(this._.eventEmitter, evt, remove, handler), this)
        }, trigger: function (evt, data) {
            return prepare(this), trigger(this, evt, data)
        }
    }, NittroEvent = _context.extend(function (target, type, data) {
        this.target = target, this.type = type, this.data = data || {}, this._ = {
            defaultPrevented: !1,
            async: !1,
            queue: null,
            promise: null
        }
    }, {
        preventDefault: function () {
            return this._.defaultPrevented = !0, this
        }, isDefaultPrevented: function () {
            return this._.defaultPrevented
        }, waitFor: function (promise) {
            if (this._.promise) throw new Error("The event's queue has already been frozen");
            return this._.queue || (this._.queue = []), this._.queue.push(promise), this._.async = !0, this
        }, isAsync: function () {
            return this._.async
        }, then: function (onfulfilled, onrejected) {
            return this._.promise || (this._.promise = this._.queue ? Promise.all(this._.queue) : Promise.resolve(), this._.queue = null), this._.promise.then(onfulfilled, onrejected)
        }
    });
    _context.register(NittroEventEmitter, "EventEmitter"), _context.register(NittroEvent, "Event")
}), _context.invoke("Nittro", function () {
    var prepare = function (self, need) {
        if (!self._) {
            if (need === !1) return !1;
            self._ = {}
        }
        if (!self._.hasOwnProperty("frozen")) {
            if (need === !1) return !1;
            self._.frozen = !1
        }
    }, Freezable = {
        freeze: function () {
            return prepare(this), this._.frozen = !0, this
        }, isFrozen: function () {
            return prepare(this, !1) !== !1 && this._.frozen
        }, _updating: function (prop) {
            if (prepare(this, !1) === !1) return this;
            if (this._.frozen) {
                var className = _context.lookupClass(this) || "object";
                throw prop && (prop = ' "' + prop + '"'), new Error("Cannot update property" + prop + " of a frozen " + className)
            }
            return this
        }
    };
    _context.register(Freezable, "Freezable")
}), _context.invoke("Nittro", function () {
    var Object = _context.extend(function () {
        this._ = {}
    }, {});
    _context.mixin(Object, "Nittro.EventEmitter"), _context.register(Object, "Object")
}), _context.invoke("Utils", function (Utils, undefined) {
    function getValue(interval) {
        return "number" == typeof interval ? interval : interval instanceof DateInterval ? interval.getLength() : DateInterval.from(interval).getLength()
    }

    function formatAuto(interval, precision, locale) {
        precision === !0 ? precision = 8 : precision || (precision = 2);
        var v, last, str = [], sign = "";
        return interval < 0 && (sign = "-", interval = -interval), intervals.some(function (i) {
            if (interval >= intervalLengths[i] && (precision--, v = interval / intervalLengths[i], v = 0 === precision ? Math.round(v) : Math.floor(v), str.push(v + " " + Utils.DateTime.i18n.getInterval(locale, i, v)), interval -= v * intervalLengths[i], 0 === precision)) return !0
        }), str.length > 2 ? (last = str.pop(), sign + str.join(", ") + " " + Utils.DateTime.i18n.getConjuction(locale) + " " + last) : sign + str.join(" " + Utils.DateTime.i18n.getConjuction(locale) + " ")
    }

    function format(interval, pattern) {
        var sign = interval < 0 ? "-" : "+";
        return interval = Math.abs(interval), (pattern + "").replace(/%(.)/g, function (m, f) {
            var v, pad = !1;
            switch (f) {
                case"%":
                    return "%";
                case"y":
                    m = intervalLengths.year;
                    break;
                case"w":
                    m = intervalLengths.week;
                    break;
                case"m":
                    pad = !0;
                case"n":
                    m = intervalLengths.month;
                    break;
                case"d":
                    pad = !0;
                case"j":
                    m = intervalLengths.day;
                    break;
                case"H":
                    pad = !0;
                case"G":
                    m = intervalLengths.hour;
                    break;
                case"i":
                    pad = !0;
                case"I":
                    m = intervalLengths.minute;
                    break;
                case"s":
                    pad = !0;
                case"S":
                    m = intervalLengths.second;
                    break;
                case"-":
                    return "-" === sign ? sign : "";
                case"+":
                    return sign;
                default:
                    throw new Error("Unknown format modifier: %" + f)
            }
            return v = Math.floor(interval / m), interval -= m * v, pad && v < 10 ? "0" + v : v
        })
    }

    var DateInterval = function (interval, locale) {
        this._ = {initialized: !1, interval: interval, locale: locale || Utils.DateTime.defaultLocale}
    };
    DateInterval.from = function (interval, locale) {
        return new DateInterval(interval, locale)
    };
    var intervals = ["year", "month", "week", "day", "hour", "minute", "second", "millisecond"], intervalLengths = {
        year: 31536e6,
        month: 26784e5,
        week: 6048e5,
        day: 864e5,
        hour: 36e5,
        minute: 6e4,
        second: 1e3,
        millisecond: 1
    };
    DateInterval.prototype.add = function (interval) {
        return this._initialize(), this._.interval += getValue(interval), this
    }, DateInterval.prototype.subtract = function (interval) {
        return this._initialize(), this._.interval -= getValue(interval), this
    }, DateInterval.prototype.isNegative = function () {
        return this._initialize(), this._.interval < 0
    }, DateInterval.prototype.getLength = function () {
        return this._initialize(), this._.interval
    }, DateInterval.prototype.valueOf = function () {
        return this.getLength()
    }, DateInterval.prototype.format = function (pattern) {
        return this._initialize(), "boolean" != typeof pattern && "number" != typeof pattern && pattern ? format(this._.interval, pattern) : formatAuto(this._.interval, pattern, this._.locale)
    }, DateInterval.prototype._initialize = function () {
        if (!this._.initialized && (this._.initialized = !0, "number" != typeof this._.interval)) {
            var interval = this._.interval;
            if (interval instanceof DateInterval) this._.interval = interval.getLength(); else {
                if ("string" != typeof interval) throw new Error("Invalid interval specification, expected string, number or a DateInterval instance");
                if (interval.match(/^\s*(?:\+|-)?\s*\d+\s*$/)) this._.interval = parseInt(interval.trim()); else {
                    var rest, res = 0;
                    if (rest = interval.replace(Utils.DateTime.i18n.getIntervalParser(this._.locale), function (_, sign, n, y, m, w, d, h, i, s, u) {
                            return sign = "-" === sign ? -1 : 1, n = parseInt(n) * sign, y && (n *= intervalLengths.year), m && (n *= intervalLengths.month), w && (n *= intervalLengths.week), d && (n *= intervalLengths.day), h && (n *= intervalLengths.hour), i && (n *= intervalLengths.minute), s && (n *= intervalLengths.second), u && (n *= intervalLengths.millisecond), res += n, ""
                        }), rest.length) throw new Error('Invalid interval specification "' + interval + '", didn\'t understand "' + rest + '"');
                    this._.interval = res
                }
            }
        }
    }, _context.register(DateInterval, "DateInterval")
}), _context.invoke("Utils", function (Strings, Arrays, DateInterval, undefined) {
    var DateTime = function (d, locale) {
        this._ = {initialized: !1, date: d || new Date, locale: locale || DateTime.defaultLocale}
    };
    DateTime.defaultLocale = "en", DateTime.from = function (s, locale) {
        return new DateTime(s, locale)
    }, DateTime.now = function () {
        return new DateTime
    }, DateTime.isDateObject = function (o) {
        return "object" == typeof o && o && o.date !== undefined && o.timezone !== undefined && o.timezone_type !== undefined
    }, DateTime.isLeapYear = function (y) {
        return y % 4 === 0 && y % 100 !== 0 || y % 400 === 0
    }, DateTime.isModifyString = function (str, locale) {
        return DateTime.i18n.getParser(locale || DateTime.defaultLocale).test(str)
    }, DateTime.getDaysInMonth = function (m, y) {
        for (; m < 0;) m += 12, y--;
        for (; m > 12;) m -= 12, y++;
        return 1 === m ? DateTime.isLeapYear(y) ? 29 : 28 : m in {3: 1, 5: 1, 8: 1, 10: 1} ? 30 : 31
    };
    var ni = function () {
        throw new Error("Not implemented!")
    }, pad = function (n) {
        return n < 10 ? "0" + n : n
    }, formatTz = function (offset) {
        return ("string" == typeof offset || offset instanceof String) && offset.match(/(\+|-)\d\d:\d\d/) ? offset : ("number" != typeof offset && (offset = parseInt(offset)), (offset < 0 ? "+" : "-") + pad(parseInt(Math.abs(offset) / 60)) + ":" + pad(Math.abs(offset) % 60))
    };
    DateTime.getLocalTzOffset = function () {
        return formatTz((new Date).getTimezoneOffset())
    }, DateTime.formatModifiers = {
        d: function (d, u) {
            return pad(u ? d.getUTCDate() : d.getDate())
        }, D: function (d, u, o) {
            return DateTime.i18n.getWeekday(o, u ? d.getUTCDay() : d.getDay(), !0)
        }, j: function (d, u) {
            return u ? d.getUTCDate() : d.getDate()
        }, l: function (d, u, o) {
            return DateTime.i18n.getWeekday(o, u ? d.getUTCDay() : d.getDay())
        }, N: function (d, u, n) {
            return n = u ? d.getUTCDay() : d.getDay(), 0 === n ? 7 : n
        }, S: function (d, u, n) {
            return n = u ? d.getUTCDate() : d.getDate(), n %= 10, 0 === n || n > 3 ? "th" : ["st", "nd", "rd"][n - 1]
        }, w: function (d, u) {
            return u ? d.getUTCDay() : d.getDay()
        }, z: function (d, u, n, m, y, M) {
            for (n = u ? d.getUTCDate() : d.getDate(), n--, y = u ? d.getUTCFullYear() : d.getFullYear(), m = 0, M = u ? d.getUTCMonth() : d.getMonth(); m < M;) n += DateTime.getDaysInMonth(m++, y);
            return n
        }, W: ni, F: function (d, u, o) {
            return DateTime.i18n.getMonth(o, u ? d.getUTCMonth() : d.getMonth())
        }, m: function (d, u) {
            return pad((u ? d.getUTCMonth() : d.getMonth()) + 1)
        }, M: function (d, u, o) {
            return DateTime.i18n.getMonth(o, u ? d.getUTCMonth() : d.getMonth(), !0)
        }, n: function (d, u) {
            return (u ? d.getUTCMonth() : d.getMonth()) + 1
        }, t: function (d, u) {
            return DateTime.getDaysInMonth(u ? d.getUTCMonth() : d.getMonth(), u ? d.getUTCFullYear() : d.getFullYear())
        }, L: function (d, u) {
            return DateTime.isLeapYear(u ? d.getUTCFullYear() : d.getFullYear()) ? 1 : 0
        }, o: ni, Y: function (d, u) {
            return u ? d.getUTCFullYear() : d.getFullYear()
        }, y: function (d, u) {
            return (u ? d.getUTCFullYear() : d.getFullYear()).toString().substr(-2)
        }, a: function (d, u, h) {
            return h = u ? d.getUTCHours() : d.getHours(), h >= 0 && h < 12 ? "am" : "pm"
        }, A: function (d, u) {
            return DateTime.formatModifiers.a(d, u).toUpperCase()
        }, g: function (d, u, h) {
            return h = u ? d.getUTCHours() : d.getHours(), 0 === h ? 12 : h > 12 ? h - 12 : h
        }, G: function (d, u) {
            return u ? d.getUTCHours() : d.getHours()
        }, h: function (d, u) {
            return pad(DateTime.formatModifiers.g(d, u))
        }, H: function (d, u) {
            return pad(u ? d.getUTCHours() : d.getHours())
        }, i: function (d, u) {
            return pad(u ? d.getUTCMinutes() : d.getMinutes())
        }, s: function (d, u) {
            return pad(u ? d.getUTCSeconds() : d.getSeconds())
        }, u: function (d, u) {
            return 1e3 * (u ? d.getUTCMilliseconds() : d.getMilliseconds())
        }, e: ni, I: ni, O: function (d, u) {
            return DateTime.formatModifiers.P(d, u).replace(":", "")
        }, P: function (d, u) {
            return u ? "+00:00" : formatTz(d.getTimezoneOffset())
        }, T: ni, Z: function (d, u) {
            return u ? 0 : d.getTimezoneOffset() * -60
        }, c: function (d, u) {
            return DateTime.from(d).format("Y-m-d\\TH:i:sP", u)
        }, r: function (d, u) {
            return DateTime.from(d).format("D, n M Y G:i:s O", u)
        }, U: function (d) {
            return Math.round(d.getTime() / 1e3)
        }
    }, DateTime.prototype.format = function (f, utc) {
        this._initialize();
        var date = this._.date, locale = this._.locale,
            pattern = Arrays.getKeys(DateTime.formatModifiers).map(Strings.escapeRegex).join("|"),
            re = new RegExp("(\\\\*)(" + pattern + ")", "g");
        return f.replace(re, function (s, c, m) {
            return c.length % 2 ? c.substr(1) + m : c + "" + DateTime.formatModifiers[m](date, utc, locale)
        })
    }, DateTime.prototype.getLocale = function () {
        return this._.locale
    }, DateTime.prototype.setLocale = function (locale) {
        if (!DateTime.i18n.hasLocale(locale)) throw new Error("Unknown locale: " + locale);
        return this._.locale = locale, this
    }, ["getTime", "getDate", "getDay", "getMonth", "getFullYear", "getHours", "getMinutes", "getSeconds", "getMilliseconds", "getTimezoneOffset", "getUTCDate", "getUTCDay", "getUTCMonth", "getUTCFullYear", "getUTCHours", "getUTCMinutes", "getUTCSeconds", "getUTCMilliseconds", "toDateString", "toISOString", "toJSON", "toLocaleDateString", "toLocaleFormat", "toLocaleTimeString", "toString", "toTimeString", "toUTCString"].forEach(function (method) {
        DateTime.prototype[method] = function () {
            return this._initialize(), this._.date[method].apply(this._.date, arguments)
        }
    }), ["setTime", "setDate", "setMonth", "setFullYear", "setHours", "setMinutes", "setSeconds", "setMilliseconds", "setUTCDate", "setUTCMonth", "setUTCFullYear", "setUTCHours", "setUTCMinutes", "setUTCSeconds", "setUTCMilliseconds"].forEach(function (method) {
        DateTime.prototype[method] = function () {
            return this._initialize(), this._.date[method].apply(this._.date, arguments), this
        }
    }), DateTime.prototype.getTimestamp = function () {
        return this._initialize(), Math.round(this._.date.getTime() / 1e3)
    }, DateTime.prototype.getDateObject = function () {
        return this._initialize(), this._.date
    }, DateTime.prototype.valueOf = function () {
        return this.getTimestamp()
    }, DateTime.prototype.modify = function (s) {
        this._initialize();
        var parts, dt, i, o, t = this._.date.getTime();
        if (s instanceof DateInterval) return this._.date = new Date(t + s.getLength()), this;
        if (parts = DateTime.i18n.getParser(this._.locale).exec(s.toLowerCase()), !parts) throw new Error("Invalid interval expression: " + s);
        if (parts[1]) t = Date.now(); else if (parts[2]) t -= 864e5; else if (parts[3]) dt = new Date, dt.setHours(this._.date.getHours(), this._.date.getMinutes(), this._.date.getSeconds(), this._.date.getMilliseconds()), t = dt.getTime(); else if (parts[4]) t += 864e5; else if (parts[5] || parts[6]) dt = new Date(t), o = parts[7] ? -1 : parts[9] ? 1 : 0, parts[10] ? dt.setFullYear(dt.getFullYear() + o, parts[5] ? 0 : 11, parts[5] ? 1 : 31) : parts[11] ? dt.setMonth(dt.getMonth() + o, parts[5] ? 1 : DateTime.getDaysInMonth(dt.getMonth() + o, dt.getFullYear())) : dt.setDate(dt.getDate() - dt.getDay() + DateTime.i18n.getWeekStart(this._.locale) + 7 * o + (parts[5] ? 0 : 6)), t = dt.getTime(); else if (parts[13] || parts[14] || parts[15]) {
            for (dt = new Date(t), o = parts[13] ? -1 : parts[15] ? 1 : 0, i = 16; i < 35 && !parts[i]; i++) ;
            i < 28 ? (i -= 16, dt.setMonth(12 * o + i, 1)) : (i -= 28, i < DateTime.i18n.getWeekStart(this._.locale) && (i += 7), dt.setDate(dt.getDate() - dt.getDay() + 7 * o + i)), t = dt.getTime()
        }
        return parts[35] || parts[36] ? (dt = new Date(t), dt.setHours(parts[36] ? 0 : 12, 0, 0, 0), t = dt.getTime()) : parts[37] && (dt = new Date(t), o = parts[37].match(/^(\d+)(?::(\d+)(?::(\d+))?)?\s*([ap]m)?/i), o[1] = parseInt(o[1].replace(/^0(\d)$/, "$1")), 12 === o[1] && "am" === o[4] ? o[1] = 0 : o[1] < 12 && "pm" === o[4] && (o[1] += 12), o[2] = o[2] !== undefined ? parseInt(o[2].replace(/^0(\d)$/, "$1")) : 0, o[3] = o[3] !== undefined ? parseInt(o[3].replace(/^0(\d)$/, "$1")) : 0, dt.setHours(o[1], o[2], o[3], 0), t = dt.getTime()), parts[38] && (t += DateInterval.from(parts[38], this._.locale).getLength()), this._.date = new Date(t), this
    }, DateTime.prototype.modifyClone = function (s) {
        return DateTime.from(this).modify(s)
    }, DateTime.prototype._initialize = function () {
        if (!this._.initialized) {
            this._.initialized = !0;
            var m, s;
            "string" == typeof this._.date ? (m = this._.date.match(/^@(\d+)$/)) ? this._.date = new Date(1e3 * m[1]) : (m = this._.date.match(/^(\d\d\d\d-\d\d-\d\d)[ T](\d\d:\d\d(?::\d\d(?:\.\d+)?)?)([-+]\d\d:?\d\d)?$/)) ? this._.date = new Date(m[1] + "T" + m[2] + (m[3] || "")) : DateTime.isModifyString(this._.date, this._.locale) ? (s = this._.date, this._.date = new Date, this.modify(s)) : this._.date = new Date(this._.date) : "number" == typeof this._.date ? this._.date = new Date(this._.date) : DateTime.isDateObject(this._.date) ? (s = this._.date.date, 3 === this._.date.timezone_type && "UTC" !== this._.date.timezone || (s += " " + this._.date.timezone), this._.date = new Date(s)) : this._.date instanceof DateTime && (this._.locale = this._.date.getLocale(), this._.date = new Date(this._.date.getTime()))
        }
    }, _context.register(DateTime, "DateTime")
}), _context.invoke("Utils", function (DateTime, Strings) {
    function buildParser(locale) {
        var i;
        if (!("months" in locale.parsers)) for (locale.parsers.months = [], i = 0; i < 12; i++) locale.parsers.months.push(Strings.escapeRegex(locale.keywords.months.full[i]) + "|" + Strings.escapeRegex(locale.keywords.months.abbrev[i]));
        if (!("weekdays" in locale.parsers)) for (locale.parsers.weekdays = [], i = 0; i < 7; i++) locale.parsers.weekdays.push(Strings.escapeRegex(locale.keywords.weekdays.full[i]) + "|" + Strings.escapeRegex(locale.keywords.weekdays.abbrev[i]));
        var parts = ["^", "(?:", "(?:", "(", locale.parsers.now, ")|", "(", locale.parsers.yesterday, ")|", "(", locale.parsers.today, ")|", "(", locale.parsers.tomorrow, ")|", "(?:", "(", locale.parsers.firstOf, ")|", "(", locale.parsers.lastOf, ")", ")\\s+(?:", "(", locale.parsers.last, ")|", "(", locale.parsers["this"], ")|", "(", locale.parsers.next, ")", ")\\s+(?:", "(", locale.parsers.year, ")|", "(", locale.parsers.month, ")|", "(", locale.parsers.week, ")", ")", "|", "(?:", "(", locale.parsers.last, ")|", "(", locale.parsers["this"], ")|", "(", locale.parsers.next, ")", ")\\s+(?:", "(", locale.parsers.months.join(")|("), ")", "|", "(", locale.parsers.weekdays.join(")|("), ")", ")", ")(?:\\s+|$)", ")?", "(?:", "(?:", locale.parsers.at, "\\s+)?", "(?:", "(", locale.parsers.noon, ")|", "(", locale.parsers.midnight, ")|", "([012]?\\d(?::[0-5]\\d(?::[0-5]\\d)?)?(?:\\s*[ap]m)?)", ")", "(?=[-+]|\\s|$)", ")?", "(", "(?:", "\\s*[-+]?\\s*\\d+\\s+", "(?:", locale.parsers.intervals.year, "|", locale.parsers.intervals.month, "|", locale.parsers.intervals.week, "|", locale.parsers.intervals.day, "|", locale.parsers.intervals.hour, "|", locale.parsers.intervals.minute, "|", locale.parsers.intervals.second, "|", locale.parsers.intervals.millisecond, ")", "(?=[-+]|\\s|$)", ")*", ")", "$"];
        return new RegExp(parts.join(""), "i")
    }

    function buildIntervalParser(locale) {
        var parts = ["\\s*([-+]?)\\s*(\\d+)\\s+", "(?:", "(", locale.parsers.intervals.year, ")|", "(", locale.parsers.intervals.month, ")|", "(", locale.parsers.intervals.week, ")|", "(", locale.parsers.intervals.day, ")|", "(", locale.parsers.intervals.hour, ")|", "(", locale.parsers.intervals.minute, ")|", "(", locale.parsers.intervals.second, ")|", "(", locale.parsers.intervals.millisecond, ")", ")\\s*"];
        return new RegExp(parts.join(""), "ig")
    }

    var i18n = DateTime.i18n = {
        locales: {}, hasLocale: function (locale) {
            return locale in i18n.locales
        }, getLocale: function (locale) {
            if (!i18n.hasLocale(locale)) throw new Error("Unknown locale: " + locale);
            return i18n.locales[locale]
        }, getMonth: function (locale, m, abbrev) {
            return i18n.getLocale(locale).keywords.months[abbrev ? "abbrev" : "full"][m]
        }, getWeekday: function (locale, d, abbrev) {
            return i18n.getLocale(locale).keywords.weekdays[abbrev ? "abbrev" : "full"][d]
        }, getConjuction: function (locale) {
            return i18n.getLocale(locale).keywords.conjuction
        }, getInterval: function (locale, unit, n) {
            return locale = i18n.getLocale(locale), n = locale.getPlural(n), locale.keywords.intervals[unit][n]
        }, getWeekStart: function (locale) {
            return i18n.getLocale(locale).weekStart
        }, getParser: function (locale) {
            return locale = i18n.getLocale(locale), locale.parser || (locale.parser = buildParser(locale)), locale.parser
        }, getIntervalParser: function (locale) {
            return locale = i18n.getLocale(locale), locale.intervalParser || (locale.intervalParser = buildIntervalParser(locale)), locale.intervalParser
        }
    }
}), _context.invoke("Utils", function (DateTime) {
    DateTime.i18n.locales.en = {
        getPlural: function (n) {
            return 1 === n ? 0 : 1
        },
        weekStart: 0,
        keywords: {
            weekdays: {
                abbrev: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                full: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]
            },
            months: {
                abbrev: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                full: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
            },
            intervals: {
                year: ["year", "years"],
                month: ["month", "months"],
                week: ["week", "weeks"],
                day: ["day", "days"],
                hour: ["hour", "hours"],
                minute: ["minute", "minutes"],
                second: ["second", "seconds"],
                millisecond: ["millisecond", "milliseconds"]
            },
            conjuction: "and"
        },
        parsers: {
            now: "now",
            today: "today",
            tomorrow: "tomorrow",
            yesterday: "yesterday",
            at: "at",
            noon: "noon",
            midnight: "midnight",
            last: "last",
            "this": "this",
            next: "next",
            firstOf: "first(?:\\s+day)?\\s+of",
            lastOf: "last(?:\\s+day)?\\s+of",
            year: "year",
            month: "month",
            week: "week",
            intervals: {
                year: "y(?:ears?)?",
                month: "mon(?:ths?)?",
                week: "w(?:eeks?)?",
                day: "d(?:ays?)?",
                hour: "h(?:ours?)?",
                minute: "min(?:utes?)?",
                second: "s(?:ec(?:onds?)?)?",
                millisecond: "millis(?:econds?)?|ms"
            }
        }
    }
}), _context.invoke("Nittro.Utils", function (Nittro, Strings, Arrays, HashMap, undefined) {
    var Tokenizer = _context.extend(function (patterns, matchCase) {
        var types = !1;
        if (!Array.isArray(patterns)) if (patterns instanceof HashMap) types = patterns.getKeys(), patterns = patterns.getValues(); else {
            var type, tmp = patterns;
            types = [], patterns = [];
            for (type in tmp) tmp.hasOwnProperty(type) && (types.push(type), patterns.push(tmp[type]))
        }
        this._ = {pattern: "(" + patterns.join(")|(") + ")", types: types, matchCase: matchCase}
    }, {
        STATIC: {
            getCoordinates: function (text, offset) {
                text = text.substr(0, offset);
                var m = text.match(/\n/g);
                return [(m ? m.length : 0) + 1, offset - ("\n" + text).lastIndexOf("\n") + 1]
            }
        }, tokenize: function (input) {
            var re, tokens, pos, n;
            return this._.types ? (re = new RegExp(this._.pattern, "gm" + (this._.matchCase ? "" : "i")), tokens = [], pos = 0, n = this._.types.length, input.replace(re, function () {
                var i, ofs = arguments[n + 1];
                for (ofs > pos && tokens.push([input.substr(pos, ofs - pos), pos, null]), i = 1; i <= n; i++) if (arguments[i] !== undefined) return tokens.push([arguments[i], ofs, this._.types[i - 1]]), void(pos = ofs + arguments[0].length);
                throw new Error("Unknown token type: " + arguments[0])
            }.bind(this)), pos + 1 < input.length && tokens.push([input.substr(pos), pos, null])) : tokens = Strings.split(input, new RegExp(this._.pattern, "m" + (this._.matchCase ? "" : "i")), !0, !0, !0), tokens
        }
    });
    _context.register(Tokenizer, "Tokenizer")
}, {
    Strings: "Utils.Strings",
    Arrays: "Utils.Arrays",
    HashMap: "Utils.HashMap"
}), _context.invoke("Nittro.Neon", function (Nittro, HashMap, Tokenizer, Strings, Arrays, DateTime, undefined) {
    var Neon = _context.extend(function () {
        this._cbStr = this._cbStr.bind(this)
    }, {
        STATIC: {
            patterns: ["'[^'\\n]*'|\"(?:\\\\.|[^\"\\\\\\n])*\"", "(?:[^#\"',:=[\\]{}()\0- !`-]|[:-][^\"',\\]})\\s])(?:[^,:=\\]})(\0- ]|:(?![\\s,\\]})]|$)|[ \\t]+[^#,:=\\]})(\0- ])*", "[,:=[\\]{}()-]", "?:#.*", "\\n[\\t ]*", "?:[\\t ]+"],
            brackets: {"{": "}", "[": "]", "(": ")"},
            consts: {
                "true": !0,
                True: !0,
                TRUE: !0,
                yes: !0,
                Yes: !0,
                YES: !0,
                on: !0,
                On: !0,
                ON: !0,
                "false": !1,
                False: !1,
                FALSE: !1,
                no: !1,
                No: !1,
                NO: !1,
                off: !1,
                Off: !1,
                OFF: !1,
                "null": null,
                Null: null,
                NULL: null
            },
            indent: "    ",
            BLOCK: 1,
            encode: function (data, options) {
                var tmp, s, isList;
                return data instanceof DateTime ? data.format("Y-m-d H:i:s O") : data instanceof NeonEntity ? (tmp = Neon.encode(data.attributes), Neon.encode(data.value) + "(" + tmp.substr(1, tmp.length - 2) + ")") : data && "object" == typeof data ? (s = [], isList = Array.isArray(data), options & Neon.BLOCK ? (Arrays.walk(data, function (k, v) {
                    v = Neon.encode(v, Neon.BLOCK), s.push(isList ? "-" : Neon.encode(k) + ":", Strings.contains(v, "\n") ? "\n" + Neon.indent + v.replace(/\n/g, "\n" + Neon.indent) : " " + v, "\n")
                }), s.length ? s.join("") : "[]") : (Arrays.walk(data, function (k, v) {
                    s.push(isList ? "" : Neon.encode(k) + ": ", Neon.encode(v), ", ")
                }), s.pop(), (isList ? "[" : "{") + s.join("") + (isList ? "]" : "}"))) : "string" != typeof data || Strings.isNumeric(data) || data.match(/[\x00-\x1F]|^\d{4}|^(true|false|yes|no|on|off|null)$/i) || !data.match(new RegExp("^" + Neon.patterns[1] + "$")) ? JSON.stringify(data) : data
            },
            decode: function (input) {
                if ("string" != typeof input) throw new Error("Invalid argument, must be a string");
                Neon.tokenizer || (Neon.tokenizer = new Tokenizer(Neon.patterns)), input = input.replace(/\r/g, "");
                var res, parser = new Neon;
                for (parser.input = input, parser.tokens = Neon.tokenizer.tokenize(input), res = parser.parse(0, new HashMap); parser.tokens[parser.n] !== undefined;) "\n" === parser.tokens[parser.n][0].charAt(0) ? parser.n++ : parser.error();
                return res
            }
        }, input: null, tokens: null, n: 0, indentTabs: null, parse: function (indent, result) {
            indent === undefined && (indent = null), result === undefined && (result = new HashMap);
            for (var t, inlineParser = null === indent, value = null, key = null, entity = null, hasValue = !1, hasKey = !1; this.n < this.tokens.length; this.n++) if (t = this.tokens[this.n][0], "," === t) (hasKey || hasValue) && inlineParser || this.error(), this.addValue(result, hasKey, key, hasValue ? value : null), hasKey = hasValue = !1; else if (":" === t || "=" === t) !hasKey && hasValue || this.error(), "string" != typeof value && "number" != typeof value && this.error("Unacceptable key"), key = Strings.toString(value), hasKey = !0, hasValue = !1; else if ("-" === t) (hasKey || hasValue || inlineParser) && this.error(), key = null, hasKey = !0; else if (Neon.brackets[t] !== undefined) hasValue ? ("(" !== t && this.error(), this.n++, entity = new NeonEntity, entity.value = value, entity.attributes = this.parse(null, new HashMap), value = entity) : (this.n++, value = this.parse(null, new HashMap)), hasValue = !0, this.tokens[this.n] !== undefined && this.tokens[this.n][0] === Neon.brackets[t] || this.error(); else {
                if ("}" === t || "]" === t || ")" === t) {
                    inlineParser || this.error();
                    break
                }
                if ("\n" === t.charAt(0)) if (inlineParser) (hasKey || hasValue) && (this.addValue(result, hasKey, key, hasValue ? value : null), hasKey = hasValue = !1); else {
                    for (; this.tokens[this.n + 1] !== undefined && "\n" === this.tokens[this.n + 1][0].charAt(0);) this.n++;
                    if (this.tokens[this.n + 1] === undefined) break;
                    var newIndent = this.tokens[this.n][0].length - 1;
                    if (null === indent && (indent = newIndent), newIndent && (null === this.indentTabs && (this.indentTabs = "\t" === this.tokens[this.n][0].charAt(1)), Strings.contains(this.tokens[this.n][0], this.indentTabs ? " " : "\t") && (this.n++, this.error("Either tabs or spaces may be used for indentation, not both"))), newIndent > indent) hasValue || !hasKey ? (this.n++, this.error("Unexpected indentation")) : this.addValue(result, null !== key, key, this.parse(newIndent, new HashMap)), newIndent = this.tokens[this.n] !== undefined ? this.tokens[this.n][0].length - 1 : 0, hasKey = !1; else {
                        if (hasValue && !hasKey) break;
                        hasKey && (this.addValue(result, null !== key, key, hasValue ? value : null), hasKey = hasValue = !1)
                    }
                    if (newIndent < indent) return result
                } else hasValue && this.error(), value = '"' === t.charAt(0) ? t.substr(1, t.length - 2).replace(/\\(?:u[0-9a-f]{4}|x[0-9a-f]{2}|.)/gi, this._cbStr) : "'" === t.charAt(0) ? t.substr(1, t.length - 2) : Neon.consts[t] !== undefined ? Neon.consts[t] : Strings.isNumeric(t) ? parseFloat(t) : t.match(/^\d\d\d\d-\d\d?-\d\d?(?:(?:[Tt]| +)\d\d?:\d\d(?::\d\d(?:\.\d*)?)? *(?:Z|[-+]\d\d?(?::?\d\d)?)?)?$/) ? DateTime.from(t) : t, hasValue = !0
            }
            return inlineParser ? (hasKey || hasValue) && this.addValue(result, hasKey, key, hasValue ? value : null) : hasValue && !hasKey ? result.length ? this.error() : result = value : hasKey && this.addValue(result, null !== key, key, hasValue ? value : null), result
        }, addValue: function (result, hasKey, key, value) {
            hasKey ? (result && result.has(key) && this.error("Duplicated key " + key), result.set(key, value)) : result.push(value)
        }, _cbStr: function (m) {
            var mapping = {t: "\t", n: "\n", r: "\r", f: "\f", b: "\b", '"': '"', "\\": "\\", "/": "/", _: "Â "};
            return mapping[m.charAt(1)] !== undefined ? mapping[m.charAt(1)] : "u" === m.charAt(1) && 6 === m.length ? String.fromCharCode(parseInt(m.substr(2), 16)) : "x" === m.charAt(1) && 4 === m.length ? String.fromCharCode(parseInt(m.substr(2), 16)) : void this.error("Invalid escape sequence " + m)
        }, error: function (msg) {
            var last = this.tokens[this.n] !== undefined ? this.tokens[this.n] : null,
                pos = Tokenizer.getCoordinates(this.input, last ? last[1] : this.input.length),
                token = last ? last[0].substr(0, 40).replace(/\n/g, "<new line>") : "end";
            throw new Error((msg || "Unexpected %s").replace(/%s/g, token) + " on line " + pos[0] + ", column " + pos[1])
        }
    }), NeonEntity = function (value, attributes) {
        this.value = value || null, this.attributes = attributes || null
    };
    _context.register(Neon, "Neon"), _context.register(NeonEntity, "NeonEntity")
}, {
    HashMap: "Utils.HashMap",
    Strings: "Utils.Strings",
    Arrays: "Utils.Arrays",
    DateTime: "Utils.DateTime",
    Tokenizer: "Nittro.Utils.Tokenizer"
}), _context.invoke("Nittro.DI", function (undefined) {
    var ServiceDefinition = _context.extend(function (factory, args, setup, run) {
        this._ = {factory: factory, args: args || {}, setup: setup || [], run: !!run}
    }, {
        getFactory: function () {
            return this._.factory
        }, setFactory: function (factory, args) {
            return this._.factory = factory, args !== undefined && (this._.args = args), this
        }, getArguments: function () {
            return this._.args
        }, setArguments: function (args) {
            return this._.args = args, this
        }, getSetup: function () {
            return this._.setup
        }, hasSetup: function () {
            return this._.setup.length > 0
        }, addSetup: function (callback) {
            return this._.setup.push(callback), this
        }, setRun: function (state) {
            return this._.run = state === undefined || !!state, this
        }, isRun: function () {
            return this._.run
        }
    });
    _context.register(ServiceDefinition, "ServiceDefinition")
}), _context.invoke("Nittro.DI", function (Nittro, ReflectionClass, ReflectionFunction, Arrays, Strings, HashMap, Neon, NeonEntity, ServiceDefinition, undefined) {
    var prepare = function (self) {
        self._ || (self._ = {}), self._.services || (self._.services = {}, self._.serviceDefs = {})
    }, ContainerMixin = {
        addService: function (name, service) {
            if (prepare(this), this._.services[name] || this._.serviceDefs[name]) throw new Error('Container already has a service named "' + name + '"');
            return this._.services[name] = service, this
        }, addServiceDefinition: function (name, definition, override) {
            if (prepare(this), !override && (this._.services[name] || this._.serviceDefs[name])) throw new Error('Container already has a service named "' + name + '"');
            return this._.serviceDefs[name] = definition, this
        }, hasServiceDefinition: function (name) {
            return prepare(this), this._.serviceDefs[name] !== undefined
        }, getServiceDefinition: function (name) {
            if (prepare(this), !this._.serviceDefs[name]) throw new Error('Container has no service "' + name + '"');
            return "string" == typeof this._.serviceDefs[name] ? this._.serviceDefs[name] = new ServiceDefinition(this._.serviceDefs[name].replace(/!$/, ""), null, null, (!!this._.serviceDefs[name].match(/!$/))) : "function" == typeof this._.serviceDefs[name] ? this._.serviceDefs[name] = new ServiceDefinition(this._.serviceDefs[name]) : this._.serviceDefs[name] instanceof ServiceDefinition || (this._.serviceDefs[name] = new ServiceDefinition(this._.serviceDefs[name].factory, this._.serviceDefs[name].args, this._.serviceDefs[name].setup, this._.serviceDefs[name].run)), this._.serviceDefs[name]
        }, getService: function (name) {
            if (prepare(this), "container" === name) return this;
            if (this._.services[name] === undefined) {
                if (!this._.serviceDefs[name]) throw new Error('Container has no service named "' + name + '"');
                this._createService(name)
            }
            return this._.services[name]
        }, hasService: function (name) {
            return prepare(this), "container" === name || this._.services[name] !== undefined || this._.serviceDefs[name] !== undefined
        }, isServiceCreated: function (name) {
            if (!this.hasService(name)) throw new Error('Container has no service named "' + name + '"');
            return !!this._.services[name]
        }, runServices: function () {
            prepare(this);
            var name, def;
            for (name in this._.serviceDefs) def = this.getServiceDefinition(name), def.isRun() && this.getService(name)
        }, invoke: function (callback, args, thisArg) {
            return prepare(this), args = this._autowireArguments(callback, args), callback.apply(thisArg || null, this._expandArguments(args))
        }, _createService: function (name) {
            var obj, service, setup, def = this.getServiceDefinition(name), factory = def.getFactory();
            if ("function" == typeof factory) {
                if (service = this.invoke(factory, def.getArguments()), !service) throw new Error('Factory failed to create service "' + name + '"')
            } else if (factory = this._toEntity(factory), service = this._expandEntity(factory, null, def.getArguments()), service === factory) throw new Error('Invalid factory for service "' + name + '"');
            if (this._.services[name] = service, def.hasSetup()) {
                setup = def.getSetup();
                for (var i = 0; i < setup.length; i++) "function" == typeof setup[i] ? this.invoke(setup[i], null, service) : (obj = this._toEntity(setup[i]), this._expandEntity(obj, service))
            }
            return service
        }, _autowireArguments: function (callback) {
            var i, a, argList = ReflectionFunction.from(callback).getArgs(),
                args = Arrays.createFrom(arguments, 1).filter(function (arg) {
                    return !!arg
                }).map(function (arg) {
                    return arg instanceof HashMap ? arg.isList() && (arg = HashMap.from(arg.getValues(), argList)) : arg = HashMap.from(arg, argList), arg
                });
            lookupArg:for (i = 0; i < argList.length; i++) {
                for (a = args.length - 1; a >= 0; a--) {
                    if (args[a].has(argList[i])) {
                        argList[i] = args[a].get(argList[i]);
                        continue lookupArg
                    }
                    if (args[a].has(i)) {
                        argList[i] = args[a].get(i);
                        continue lookupArg
                    }
                }
                {
                    if (!this.hasService(argList[i])) throw new Error('Cannot autowire argument "' + argList[i] + '" of function');
                    argList[i] = this.getService(argList[i])
                }
            }
            return argList
        }, _expandArguments: function (args) {
            for (var i = 0; i < args.length; i++) args[i] = this._expandArg(args[i]);
            return args
        }, _expandArg: function (arg) {
            return arg instanceof NeonEntity ? this._expandEntity(arg) : "string" == typeof arg && arg.match(/^@\S+$/) ? this.getService(arg.substr(1)) : arg
        }, _toEntity: function (str) {
            var m = str.match(/^([^\(]+)\((.*)\)$/);
            return m ? new NeonEntity(m[1], Neon.decode("[" + m[2] + "]")) : new NeonEntity(str, new HashMap)
        }, _expandEntity: function (entity, context, mergeArgs) {
            var m, obj, method, args;
            if (m = entity.value.match(/^(?:(@)?([^:].*?))?(?:::(.+))?$/)) {
                if (m[2]) obj = m[1] ? this.getService(m[2]) : ReflectionClass.getClass(m[2]); else {
                    if (!context) throw new Error("No context for calling " + entity.value + " given");
                    obj = context
                }
                if (m[3] !== undefined) return method = m[3], args = this._autowireArguments(obj[method], entity.attributes, mergeArgs), obj[method].apply(obj, this._expandArguments(args));
                if (m[1]) {
                    if (entity.attributes.length) throw new Error('Invalid entity "' + entity.value + '"');
                    return obj
                }
                return args = this._autowireArguments(obj, entity.attributes, mergeArgs), ReflectionClass.from(obj).newInstanceArgs(this._expandArguments(args))
            }
            return entity
        }
    };
    _context.register(ContainerMixin, "ContainerMixin")
}, {
    ReflectionClass: "Utils.ReflectionClass",
    ReflectionFunction: "Utils.ReflectionFunction",
    Arrays: "Utils.Arrays",
    Strings: "Utils.Strings",
    HashMap: "Utils.HashMap",
    Neon: "Nittro.Neon.Neon",
    NeonEntity: "Nittro.Neon.NeonEntity"
}), _context.invoke("Nittro.DI", function (ContainerMixin, Arrays, HashMap, ReflectionClass, NeonEntity, undefined) {
    function traverse(cursor, path, create) {
        "string" == typeof path && (path = path.split(/\./g));
        var i, p, n = path.length;
        for (i = 0; i < n; i++) {
            if (p = path[i], Array.isArray(cursor) && p.match(/^\d+$/) && (p = parseInt(p)), cursor[p] === undefined) {
                if (!create) return undefined;
                cursor[p] = {}
            }
            cursor = cursor[p]
        }
        return cursor
    }

    var Container = _context.extend(function (config) {
        config || (config = {}), this._ = {
            params: Arrays.mergeTree({}, config.params || null),
            serviceDefs: Arrays.mergeTree({}, config.services || null),
            services: {},
            factories: Arrays.mergeTree({}, config.factories || null)
        }
    }, {
        hasParam: function (name) {
            return traverse(this._.params, name) !== undefined
        }, getParam: function (name, def) {
            var value = traverse(this._.params, name);
            return value !== undefined ? value : def !== undefined ? def : null
        }, setParam: function (name, value) {
            name = name.split(/\./g);
            var p = name.pop(), cursor = this._.params;
            return name.length && (cursor = traverse(cursor, name, !0)), Array.isArray(cursor) && p.match(/^\d+$/) && (p = parseInt(p)), cursor[p] = value, this
        }, hasFactory: function (name) {
            return this._.factories[name] !== undefined
        }, addFactory: function (name, factory, params) {
            return "string" == typeof factory ? this._.factories[name] = factory : this._.factories[name] = {
                callback: factory,
                params: params || null
            }, this
        }, create: function (name, args) {
            if (!this.hasFactory(name)) throw new Error('No factory named "' + name + '" has been registered');
            var factory = this._.factories[name];
            return "string" == typeof factory ? this._.factories[name] = factory = this._toEntity(factory) : factory.params instanceof HashMap || (factory.params = new HashMap(factory.params)), factory instanceof NeonEntity ? this._expandEntity(factory, null, args) : (args = this._autowireArguments(factory.callback, factory.params, args), factory.callback.apply(null, this._expandArguments(args)))
        }, _expandArg: function (arg) {
            return "string" == typeof arg && arg.indexOf("%") > -1 ? arg.match(/^%[^%]+%$/) ? this.getParam(arg.replace(/^%|%$/g, "")) : arg.replace(/%([a-z0-9_.-]+)%/gi, function () {
                return this.getParam(arguments[1])
            }.bind(this)) : this.__expandArg(arg)
        }
    });
    _context.mixin(Container, ContainerMixin, {_expandArg: "__expandArg"}), _context.register(Container, "Container")
}, {
    Arrays: "Utils.Arrays",
    HashMap: "Utils.HashMap",
    ReflectionClass: "Utils.ReflectionClass",
    NeonEntity: "Nittro.Neon.NeonEntity"
}), _context.invoke("Nittro.DI", function (Arrays) {
    var BuilderExtension = _context.extend(function (containerBuilder, config) {
        this._ = {containerBuilder: containerBuilder, config: config}
    }, {
        load: function () {
        }, setup: function () {
        }, _getConfig: function (defaults) {
            return defaults && (this._.config = Arrays.mergeTree({}, defaults, this._.config)), this._.config
        }, _getContainerBuilder: function () {
            return this._.containerBuilder
        }
    });
    _context.register(BuilderExtension, "BuilderExtension")
}, {Arrays: "Utils.Arrays"}), _context.invoke("Nittro.DI", function (Container, BuilderExtension, undefined) {
    var ContainerBuilder = _context.extend(Container, function (config) {
        config || (config = {}), ContainerBuilder.Super.call(this, config), this._.extensions = config.extensions || {}
    }, {
        addExtension: function (name, extension) {
            if (this._.extensions[name] !== undefined) throw new Error('Container builder already has an extension called "' + name + '"');
            return this._.extensions[name] = extension, this
        }, createContainer: function () {
            return this._prepareExtensions(), this._loadExtensions(), this._setupExtensions(), new Container({
                params: this._.params,
                services: this._.serviceDefs,
                factories: this._.factories
            })
        }, _prepareExtensions: function () {
            var name, extension;
            for (name in this._.extensions) {
                if (extension = this._.extensions[name], "function" == typeof extension ? extension = this.invoke(extension, {
                        containerBuilder: this,
                        config: this._.params[name] || {}
                    }) : "string" == typeof extension && (extension = this._expandEntity(this._toEntity(extension), null, {
                        containerBuilder: this,
                        config: this._.params[name] || {}
                    })), !(extension instanceof BuilderExtension)) throw new Error('Extension "' + name + '" is not an instance of Nittro.DI.BuilderExtension');
                this._.extensions[name] = extension
            }
        }, _loadExtensions: function () {
            for (var name in this._.extensions) this._.extensions[name].load()
        }, _setupExtensions: function () {
            for (var name in this._.extensions) this._.extensions[name].setup()
        }
    });
    _context.register(ContainerBuilder, "ContainerBuilder")
}), _context.invoke("Nittro.Ajax", function (Nittro, Url, undefined) {
    var Request = _context.extend("Nittro.Object", function (url, method, data) {
        this._ = {
            url: Url.from(url),
            method: (method || "GET").toUpperCase(),
            data: data || {},
            headers: {},
            normalized: !1,
            dispatched: !1,
            deferred: {fulfill: null, reject: null, promise: null},
            abort: null,
            aborted: !1,
            response: null
        }, this._.deferred.promise = new Promise(function (fulfill, reject) {
            this._.deferred.fulfill = fulfill, this._.deferred.reject = reject
        }.bind(this))
    }, {
        getUrl: function () {
            return this._normalize(), this._.url
        }, getMethod: function () {
            return this._.method
        }, isGet: function () {
            return "GET" === this._.method
        }, isPost: function () {
            return "POST" === this._.method
        }, isMethod: function (method) {
            return method.toUpperCase() === this._.method
        }, getData: function () {
            return this._normalize(), this._.data
        }, getHeaders: function () {
            return this._.headers
        }, setUrl: function (url) {
            return this._updating("url"), this._.url = Url.from(url), this
        }, setMethod: function (method) {
            return this._updating("method"), this._.method = method.toLowerCase(), this
        }, setData: function (k, v) {
            if (this._updating("data"), null === k) this._.data = {}; else if (v === undefined && "object" == typeof k) for (v in k) k.hasOwnProperty(v) && (this._.data[v] = k[v]); else this._.data[k] = v;
            return this
        }, setHeader: function (header, value) {
            return this._updating("headers"), this._.headers[header] = value, this
        }, setHeaders: function (headers) {
            this._updating("headers");
            for (var header in headers) headers.hasOwnProperty(header) && (this._.headers[header] = headers[header]);
            return this
        }, setDispatched: function (abort) {
            if (this._.dispatched) throw new Error("Request has already been dispatched");
            if ("function" != typeof abort) throw new Error('"abort" must be a function');
            return this._.dispatched = !0, this._.abort = abort, this
        }, isDispatched: function () {
            return this._.dispatched
        }, setFulfilled: function (response) {
            return response && this.setResponse(response), this._.deferred.fulfill(this.getResponse()), this
        }, setRejected: function (reason) {
            return this._.deferred.reject(reason), this
        }, then: function (onfulfilled, onrejected) {
            return this._.deferred.promise.then(onfulfilled, onrejected)
        }, abort: function () {
            return this._.abort && !this._.aborted && this._.abort(), this._.aborted = !0, this
        }, isAborted: function () {
            return this._.aborted
        }, setResponse: function (response) {
            return this._.response = response, this
        }, getResponse: function () {
            return this._.response
        }, _normalize: function () {
            !this._.normalized && this.isFrozen() && (this._.normalized = !0, "GET" !== this._.method && "HEAD" !== this._.method || (this._.url.addParams(Nittro.Forms && Nittro.Forms.FormData && this._.data instanceof Nittro.Forms.FormData ? this._.data.exportData(!0) : this._.data), this._.data = {}))
        }
    });
    _context.mixin(Request, "Nittro.Freezable"), _context.register(Request, "Request")
}, {Url: "Utils.Url"}), _context.invoke("Nittro.Ajax", function () {
    var Response = _context.extend(function (status, payload, headers) {
        this._ = {status: status, payload: payload, headers: headers}
    }, {
        getStatus: function () {
            return this._.status
        }, getPayload: function () {
            return this._.payload
        }, getHeader: function (name) {
            return this._.headers[name.toLowerCase()]
        }, getAllHeaders: function () {
            return this._.headers
        }
    });
    _context.register(Response, "Response")
}), _context.invoke("Nittro.Ajax", function (Request, Arrays, Url) {
    var Service = _context.extend("Nittro.Object", function (options) {
        Service.Super.call(this), this._.options = Arrays.mergeTree({}, Service.defaults, options), this._.transport = null, this._.options.allowOrigins ? Array.isArray(this._.options.allowOrigins) || (this._.options.allowOrigins = this._.options.allowOrigins.split(/\s*,\s*/g)) : this._.options.allowOrigins = [], this._.options.allowOrigins.push(Url.fromCurrent().getOrigin())
    }, {
        STATIC: {defaults: {allowOrigins: null}}, setTransport: function (transport) {
            return this._.transport = transport, this
        }, addTransport: function (transport) {
            return console.log("The Nittro.Ajax.Service.addTransport() method is deprecated, please use setTransport instead"), this.setTransport(transport)
        }, supports: function (url, method, data) {
            return this._.transport.supports(url, method, data)
        }, isAllowedOrigin: function (url) {
            return this._.options.allowOrigins.indexOf(Url.from(url).getOrigin()) > -1
        }, get: function (url, data) {
            return this.dispatch(this.createRequest(url, "get", data))
        }, post: function (url, data) {
            return this.dispatch(this.createRequest(url, "post", data))
        }, createRequest: function (url, method, data) {
            if (!this.isAllowedOrigin(url)) throw new Error('The origin of the URL "' + url + '" is not in the list of allowed origins');
            if (!this.supports(url, method, data)) throw new Error("The request with the specified URL, method and data isn't supported by the AJAX transport");
            var request = new Request(url, method, data);
            return this.trigger("request-created", {request: request}), request
        }, dispatch: function (request) {
            return request.freeze(), this._.transport.dispatch(request)
        }
    });
    _context.register(Service, "Service")
}, {
    Arrays: "Utils.Arrays",
    Url: "Utils.Url"
}), _context.invoke("Nittro.Ajax.Transport", function (Nittro, Response, Url) {
    var Native = _context.extend(function () {
    }, {
        STATIC: {
            createXhr: function () {
                if (window.XMLHttpRequest) return new XMLHttpRequest;
                if (window.ActiveXObject) try {
                    return new ActiveXObject("Msxml2.XMLHTTP")
                } catch (e) {
                    return new ActiveXObject("Microsoft.XMLHTTP")
                }
            }
        }, supports: function (url, method, data) {
            return !(data && Nittro.Forms && data instanceof Nittro.Forms.FormData && data.isUpload() && !window.FormData) && !(!(window.XMLHttpRequest && "withCredentials" in XMLHttpRequest.prototype) && Url.fromCurrent().compare(url) >= Url.PART.PORT)
        }, dispatch: function (request) {
            var xhr = Native.createXhr(), adv = this._checkSupport(request, xhr), abort = xhr.abort.bind(xhr);
            if (request.isAborted()) return request.setRejected(this._createError(request, xhr, {type: "abort"})), request;
            this._bindEvents(request, xhr, adv), xhr.open(request.getMethod(), request.getUrl().toAbsolute(), !0);
            var data = this._formatData(request, xhr);
            return this._addHeaders(request, xhr), xhr.send(data), request.setDispatched(abort), request
        }, _checkSupport: function (request, xhr) {
            var adv;
            if (!((adv = "addEventListener" in xhr) || "onreadystatechange" in xhr)) throw new Error("Unsupported XHR implementation");
            return adv
        }, _bindEvents: function (request, xhr, adv) {
            function onLoad(evt) {
                done || (done = !0, xhr.status >= 200 && xhr.status < 300 ? request.setFulfilled(self._createResponse(xhr)) : request.setRejected(self._createError(request, xhr, evt)))
            }

            function onError(evt) {
                done || (done = !0, request.setRejected(self._createError(request, xhr, evt)))
            }

            function onProgress(evt) {
                request.trigger("progress", {
                    lengthComputable: evt.lengthComputable,
                    loaded: evt.loaded,
                    total: evt.total
                })
            }

            var self = this, done = !1;
            adv ? (xhr.addEventListener("load", onLoad, !1), xhr.addEventListener("error", onError, !1), xhr.addEventListener("abort", onError, !1), "upload" in xhr && xhr.upload.addEventListener("progress", onProgress, !1)) : (xhr.onreadystatechange = function () {
                4 === xhr.readyState && (xhr.status >= 200 && xhr.status < 300 ? onLoad() : onError())
            }, "ontimeout" in xhr && (xhr.ontimeout = onError), "onerror" in xhr && (xhr.onerror = onError), "onload" in xhr && (xhr.onload = onLoad))
        }, _addHeaders: function (request, xhr) {
            var h, headers = request.getHeaders();
            for (h in headers) headers.hasOwnProperty(h) && xhr.setRequestHeader(h, headers[h]);
            headers.hasOwnProperty("X-Requested-With") || xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest")
        }, _formatData: function (request, xhr) {
            var data = request.getData();
            return Nittro.Forms && data instanceof Nittro.Forms.FormData ? (data = data.exportData(request.isGet() || request.isMethod("HEAD")), window.FormData && data instanceof window.FormData || (data = Url.buildQuery(data, !0), xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"))) : (data = Url.buildQuery(data), xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")), data
        }, _createResponse: function (xhr) {
            var payload, headers = {};
            return (xhr.getAllResponseHeaders() || "").trim().split(/\r\n/g).forEach(function (header) {
                header && !header.match(/^\s*$/) && (header = header.match(/^\s*([^:]+):\s*(.+?)\s*$/), headers[header[1].toLowerCase()] = header[2])
            }), payload = headers["content-type"] && "application/json" === headers["content-type"].split(/;/)[0] ? JSON.parse(xhr.responseText || "{}") : xhr.responseText, new Response(xhr.status, payload, headers)
        }, _createError: function (request, xhr, evt) {
            return 4 === xhr.readyState && 0 !== xhr.status && request.setResponse(this._createResponse(xhr)), evt && "abort" === evt.type ? {
                type: "abort",
                status: null,
                request: request
            } : 0 === xhr.status ? {
                type: "connection",
                status: null,
                request: request
            } : xhr.status < 200 || xhr.status >= 300 ? {
                type: "response",
                status: xhr.status,
                request: request
            } : {type: "unknown", status: xhr.status, request: request}
        }
    });
    _context.register(Native, "Native")
}, {Url: "Utils.Url", Response: "Nittro.Ajax.Response"}), _context.invoke("Nittro.Page", function (DOM, undefined) {
    var Snippet = _context.extend(function (id, phase) {
        this._ = {
            id: id,
            container: !1,
            phase: "number" == typeof phase ? phase : Snippet.INACTIVE,
            data: {},
            handlers: [[], [], [], []]
        }
    }, {
        STATIC: {INACTIVE: -1, PREPARE_SETUP: 0, RUN_SETUP: 1, PREPARE_TEARDOWN: 2, RUN_TEARDOWN: 3},
        getId: function () {
            return this._.id
        },
        setup: function (prepare, run) {
            return prepare && !run && (run = prepare, prepare = null), prepare && (this._.phase === Snippet.PREPARE_SETUP ? prepare(this.getElement()) : this._.handlers[Snippet.PREPARE_SETUP].push(prepare)), run && (this._.phase === Snippet.RUN_SETUP ? run(this.getElement()) : this._.handlers[Snippet.RUN_SETUP].push(run)), this
        },
        teardown: function (prepare, run) {
            return prepare && !run && (run = prepare, prepare = null), prepare && (this._.phase === Snippet.PREPARE_TEARDOWN ? prepare(this.getElement()) : this._.handlers[Snippet.PREPARE_TEARDOWN].push(prepare)), run && (this._.phase === Snippet.RUN_TEARDOWN ? run(this.getElement()) : this._.handlers[Snippet.RUN_TEARDOWN].push(run)), this
        },
        runPhase: function (phase) {
            console.info(phase + " " + this._.phase)
            console.trace()
            if (phase === Snippet.INACTIVE) this._.phase = phase, console.info(this._.handlers), this._.handlers.forEach(function (queue) {
                console.info(queue)
                queue.splice(0, queue.length)
                console.info(queue)
            }); else if (phase - 1 === this._.phase) {
                this._.phase = phase;
                var elm = this.getElement();
                this._.handlers[this._.phase].forEach(function (handler) {
                    handler(elm)
                }), this._.handlers[this._.phase].splice(0, this._.handlers[this._.phase].length)
            }
            console.info(this._.phase)
            return this
        },
        getPhase: function () {
            return this._.phase
        },
        getData: function (key, def) {
            return key in this._.data ? this._.data[key] : def === undefined ? null : def
        },
        setData: function (key, value) {
            return this._.data[key] = value, this
        },
        setContainer: function () {
            return this._.container = !0, this
        },
        isContainer: function () {
            return this._.container
        },
        getElement: function () {
            return DOM.getById(this._.id)
        }
    });
    _context.register(Snippet, "Snippet")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Page", function (DOM) {
    var Helpers = {
        buildContent: function (elem, html) {
            return elem = elem.split(/\./g), elem[0] = DOM.create(elem[0]), elem.length > 1 && DOM.addClass.apply(DOM, elem), elem = elem[0], DOM.html(elem, html), elem
        }, prepareDynamicContainer: function (snippet) {
            var elem = snippet.getElement(), params = {
                id: snippet.getId(),
                mask: new RegExp("^" + DOM.getData(elem, "dynamic-mask") + "$"),
                element: DOM.getData(elem, "dynamic-element") || "div",
                sort: DOM.getData(elem, "dynamic-sort") || "append",
                sortCache: DOM.getData(elem, "dynamic-sort-cache") !== !1 && null
            };
            return snippet.setContainer(), snippet.setData("_snippet_container", params), params
        }, computeSortedSnippets: function (container, snippets, changeset) {
            var sortData = Helpers._getSortData(container.getData("_snippet_container"), container.getElement(), changeset);
            return Helpers._mergeSortData(sortData, snippets), Helpers._applySortData(sortData)
        }, applySortedSnippets: function (container, ids, snippets) {
            var tmp, i = 0, n = ids.length;
            for (tmp = container.firstElementChild; i < n && ids[i] in snippets && !snippets[ids[i]].element;) container.insertBefore(snippets[ids[i]].content, tmp), i++;
            for (; n > i && ids[n - 1] in snippets && !snippets[ids[n - 1]].element;) n--;
            for (; i < n; i++) ids[i] in snippets && (snippets[ids[i]].element ? snippets[ids[i]].element.previousElementSibling !== (i > 0 ? DOM.getById(ids[i - 1]) : null) && container.insertBefore(snippets[ids[i]].element, i > 0 ? DOM.getById(ids[i - 1]).nextElementSibling : container.firstElementChild) : container.insertBefore(snippets[ids[i]].content, DOM.getById(ids[i - 1]).nextElementSibling));
            for (; n < ids.length;) container.appendChild(snippets[ids[n]].content), n++
        }, _applySortData: function (sortData) {
            var id, sorted = [];
            for (id in sortData.snippets) sortData.snippets.hasOwnProperty(id) && sorted.push({
                id: id,
                values: sortData.snippets[id]
            });
            return sorted.sort(Helpers._compareSnippets.bind(null, sortData.descriptor)), sorted.map(function (snippet) {
                return snippet.id
            })
        }, _compareSnippets: function (descriptor, a, b) {
            var i, n, v;
            for (i = 0, n = descriptor.length; i < n; i++) if (v = a.values[i] < b.values[i] ? -1 : a.values[i] > b.values[i] ? 1 : 0, 0 !== v) return v * (descriptor[i].asc ? 1 : -1);
            return 0
        }, _getSortData: function (params, elem, changeset) {
            var sortData = params.sortCache;
            if (sortData) for (var id in sortData.snippets) sortData.snippets.hasOwnProperty(id) && (id in changeset.remove || !DOM.getById(id)) && delete sortData.snippets[id]; else sortData = Helpers._buildSortData(params, elem, changeset), params.sortCache !== !1 && (params.sortCache = sortData);
            return sortData
        }, _buildSortData: function (params, elem, changeset) {
            var sortData = {
                descriptor: params.sort.trim().split(/\s*,\s*/g).map(Helpers._parseDescriptor.bind(null, params.id)),
                snippets: {}
            }, children = {};
            return DOM.getChildren(elem).forEach(function (child) {
                child.id in changeset.remove || child.id in changeset.update || (children[child.id] = {content: child})
            }), Helpers._mergeSortData(sortData, children), sortData
        }, _mergeSortData: function (sortData, snippets) {
            for (var id in snippets) snippets.hasOwnProperty(id) && (sortData.snippets[id] = Helpers._extractSortData(snippets[id].content, sortData.descriptor))
        }, _extractSortData: function (elem, descriptor) {
            return descriptor.map(function (field) {
                return field.extractor(elem)
            })
        }, _parseDescriptor: function (id, descriptor) {
            var sel, attr, prop, asc, m = descriptor.match(/^(.+?)(?:\[(.+?)\])?(?:\((.+?)\))?(?:\s+(.+?))?$/);
            if (!m) throw new Error("Invalid sort descriptor: " + descriptor);
            if (sel = m[1], attr = m[2], prop = m[3], asc = m[4], sel.match(/^[^.]|[\s#\[>+:]/)) throw new TypeError("Invalid selector for sorted insert mode in container #" + id);
            return sel = sel.substr(1), asc = !asc || /^[1tay]/i.test(asc), attr ? {
                extractor: Helpers._getAttrExtractor(sel, attr),
                asc: asc
            } : prop ? {
                extractor: Helpers._getDataExtractor(sel, prop),
                asc: asc
            } : {extractor: Helpers._getTextExtractor(sel), asc: asc}
        }, _getAttrExtractor: function (sel, attr) {
            return function (elem) {
                return elem = elem.getElementsByClassName(sel), elem.length ? elem[0].getAttribute(attr) || null : null
            }
        }, _getDataExtractor: function (sel, prop) {
            return function (elem) {
                return elem = elem.getElementsByClassName(sel), elem.length ? DOM.getData(elem[0], prop, null) : null
            }
        }, _getTextExtractor: function (sel) {
            return function (elem) {
                return elem = elem.getElementsByClassName(sel), elem.length ? elem[0].textContent : null
            }
        }
    };
    _context.register(Helpers, "SnippetManagerHelpers")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Page", function (Helpers, Snippet, DOM, Arrays, undefined) {
    var SnippetManager = _context.extend("Nittro.Object", function () {
        SnippetManager.Super.call(this), this._.snippets = {}, this._.containerCache = null, this._.currentPhase = Snippet.INACTIVE
    }, {
        getSnippet: function (id) {
            return this._.snippets[id] || (this._.snippets[id] = new Snippet(id, this._.currentPhase)), this._.snippets[id]
        }, isSnippet: function (elem) {
            return ("string" == typeof elem ? elem : elem.id) in this._.snippets
        }, setup: function () {
            this.trigger("after-update"), this._runSnippetsPhase(this._.snippets, Snippet.PREPARE_SETUP), this._runSnippetsPhase(this._.snippets, Snippet.RUN_SETUP)
        }, getRemoveTargets: function (elem) {
            var sel = DOM.getData(elem, "dynamic-remove");
            return sel ? DOM.find(sel) : []
        }, computeChanges: function (snippets, removeTargets) {
            this._clearDynamicContainerCache();
            var changeset = {remove: {}, update: {}, add: {}, containers: {}};
            return this._resolveRemovals(removeTargets, changeset), this._resolveUpdates(snippets, changeset), this._resolveDynamicSnippets(changeset), changeset
        }, applyChanges: function (changeset) {
            var teardown = Arrays.mergeTree({}, changeset.remove, changeset.update);
            return this._runSnippetsPhase(teardown, Snippet.PREPARE_TEARDOWN), this._runSnippetsPhase(teardown, Snippet.RUN_TEARDOWN), this._runSnippetsPhase(teardown, Snippet.INACTIVE), this.trigger("before-update", changeset), this._applyRemove(changeset.remove), this._applyUpdate(changeset.update), this._applyAdd(changeset.add, changeset.containers), this._applyDynamic(changeset.containers, Arrays.mergeTree({}, changeset.update, changeset.add)), this.trigger("after-update", changeset), this._runSnippetsPhaseOnNextFrame(this._.snippets, Snippet.PREPARE_SETUP).then(function () {
                this._runSnippetsPhase(this._.snippets, Snippet.RUN_SETUP)
            }.bind(this))
        }, cleanupDescendants: function (elem, changeset) {
            var id, snippet, snippets = changeset ? changeset.remove : {};
            for (id in this._.snippets) !this._.snippets.hasOwnProperty(id) || id in snippets || (snippet = this._.snippets[id].getElement(), snippet !== elem && DOM.contains(elem, snippet) && (snippets[id] = {
                element: snippet,
                isDescendant: !0
            }));
            changeset || (this._runSnippetsPhase(snippets, Snippet.PREPARE_TEARDOWN), this._runSnippetsPhase(snippets, Snippet.RUN_TEARDOWN), this._runSnippetsPhase(snippets, Snippet.INACTIVE))
        }, _resolveRemovals: function (removeTargets, changeset) {
            removeTargets.forEach(function (elem) {
                changeset.remove[elem.id] = {element: elem}, this.cleanupDescendants(elem, changeset)
            }.bind(this))
        }, _resolveUpdates: function (snippets, changeset) {
            var id, elem, params;
            for (id in snippets) snippets.hasOwnProperty(id) && (elem = DOM.getById(id), elem ? (this.cleanupDescendants(elem, changeset), id in changeset.remove && (params = this._resolveAddition(id, snippets[id])) ? changeset.add[id] = params : changeset.update[id] = this._resolveUpdate(elem, snippets[id])) : (params = this._resolveAddition(id, snippets[id])) && (changeset.add[id] = params))
        }, _resolveDynamicSnippets: function (changeset) {
            var id, type, cid, params;
            for (type in{
                update: 1,
                add: 1
            }) for (id in changeset[type]) changeset[type].hasOwnProperty(id) && (cid = changeset[type][id].container) && (params = this._getDynamicContainerParams(cid), "prepend" !== params.sort && "append" !== params.sort ? (changeset.containers[cid] || (changeset.containers[cid] = {}), changeset.containers[cid][id] = changeset[type][id]) : changeset[type][id].action = params.sort);
            for (cid in changeset.containers) changeset.containers.hasOwnProperty(cid) && (changeset.containers[cid] = Helpers.computeSortedSnippets(this.getSnippet(cid), changeset.containers[cid], changeset))
        }, _resolveUpdate: function (elem, content) {
            return {
                element: elem,
                content: Helpers.buildContent(elem.tagName, content),
                container: DOM.hasClass(elem.parentNode, "nittro-snippet-container") ? elem.parentNode.id : null
            }
        }, _resolveAddition: function (id, content) {
            var elem, params = this._getDynamicContainerParamsForId(id);
            return params ? (elem = Helpers.buildContent(params.element, content), elem.id = id, {
                content: elem,
                container: params.id
            }) : null
        }, _runSnippetsPhase: function (snippets, phase) {
            this._.currentPhase = phase;
            for (var id in snippets) snippets.hasOwnProperty(id) && this.getSnippet(id).runPhase(phase)
        }, _runSnippetsPhaseOnNextFrame: function (snippets, phase) {
            return new Promise(function (fulfill) {
                window.requestAnimationFrame(function () {
                    this._runSnippetsPhase(snippets, phase), fulfill()
                }.bind(this))
            }.bind(this))
        }, _applyRemove: function (snippets) {
            for (var id in snippets) snippets.hasOwnProperty(id) && (snippets[id].isDescendant || snippets[id].element.parentNode.removeChild(snippets[id].element), id in this._.snippets && delete this._.snippets[id])
        }, _applyUpdate: function (snippets) {
            for (var id in snippets) snippets.hasOwnProperty(id) && (DOM.empty(snippets[id].element), DOM.append(snippets[id].element, Arrays.createFrom(snippets[id].content.childNodes)))
        }, _applyAdd: function (snippets, containers) {
            for (var id in snippets) !snippets.hasOwnProperty(id) || snippets[id].container in containers || ("prepend" === snippets[id].action ? DOM.prepend(snippets[id].container, snippets[id].content) : DOM.append(snippets[id].container, snippets[id].content))
        }, _applyDynamic: function (containers, snippets) {
            for (var cid in containers) containers.hasOwnProperty(cid) && Helpers.applySortedSnippets(this.getSnippet(cid).getElement(), containers[cid], snippets)
        }, _getDynamicContainerCache: function () {
            return null === this._.containerCache && (this._.containerCache = DOM.getByClassName("nittro-snippet-container").map(function (elem) {
                return elem.id
            })), this._.containerCache
        }, _clearDynamicContainerCache: function () {
            this._.containerCache = null
        }, _getDynamicContainerParams: function (id) {
            var container = this.getSnippet(id);
            return container.isContainer() ? container.getData("_snippet_container") : Helpers.prepareDynamicContainer(container)
        }, _getDynamicContainerParamsForId: function (id) {
            var i, n, params, cache = this._getDynamicContainerCache();
            for (i = 0, n = cache.length; i < n; i++) if (params = this._getDynamicContainerParams(cache[i]), params.mask.test(id)) return params;
            return window.console && console.error("Dynamic snippet #" + id + " has no container"), null
        }
    });
    _context.register(SnippetManager, "SnippetManager")
}, {
    Helpers: "Nittro.Page.SnippetManagerHelpers",
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays"
}), _context.invoke("Nittro.Page", function () {
    var SnippetAgent = _context.extend(function (page, snippetManager) {
        this._ = {
            page: page,
            snippetManager: snippetManager
        }, this._.page.on("transaction-created", this._initTransaction.bind(this))
    }, {
        _initTransaction: function (evt) {
            var data = {removeTargets: evt.data.context.element ? this._.snippetManager.getRemoveTargets(evt.data.context.element) : []};
            evt.data.transaction.on("ajax-response", this._handleResponse.bind(this, data))
        }, _handleResponse: function (data, evt) {
            var changeset, payload = evt.data.response.getPayload();
            (payload.snippets || data.removeTargets.length) && (changeset = this._.snippetManager.computeChanges(payload.snippets || {}, data.removeTargets), evt.waitFor(this._applyChangeset(evt.target, changeset)))
        }, _applyChangeset: function (transaction, changeset) {
            return transaction.trigger("snippets-apply", {changeset: changeset}).then(function () {
                this._.snippetManager.applyChanges(changeset)
            }.bind(this))
        }
    });
    _context.register(SnippetAgent, "SnippetAgent")
}), _context.invoke("Nittro.Page", function (Arrays) {
    var AjaxAgent = _context.extend(function (page, ajax, options) {
        this._ = {
            page: page,
            ajax: ajax,
            options: Arrays.mergeTree({}, AjaxAgent.defaults, options)
        }, this._.page.on("before-transaction", this._checkTransaction.bind(this)), this._.page.on("transaction-created", this._initTransaction.bind(this))
    }, {
        STATIC: {defaults: {whitelistRedirects: !1}}, _checkTransaction: function (evt) {
            this._.ajax.isAllowedOrigin(evt.data.url) && this._.ajax.supports(evt.data.url, evt.data.context.method, evt.data.context.data) || evt.preventDefault()
        }, _initTransaction: function (evt) {
            var data = {request: this._.ajax.createRequest(evt.data.transaction.getUrl(), evt.data.context.method, evt.data.context.data)};
            evt.data.transaction.on("dispatch", function (evt) {
                evt.waitFor(this._dispatch(evt.target, data))
            }.bind(this)), evt.data.transaction.on("abort", this._abort.bind(this, data))
        }, _dispatch: function (transaction, data) {
            return transaction.trigger("ajax-request", {request: data.request}).then(this._.ajax.dispatch.bind(this._.ajax, data.request)).then(this._handleResponse.bind(this, transaction, data))
        }, _abort: function (data) {
            data.request.abort()
        }, _handleResponse: function (transaction, data, response) {
            return transaction.trigger("ajax-response", {response: response}).then(function () {
                var payload = response.getPayload();
                return payload.postGet && transaction.setUrl(payload.url), "redirect" in payload ? (this._.options.whitelistRedirects ? payload.allowAjax : payload.allowAjax !== !1) && this._.ajax.isAllowedOrigin(payload.redirect) ? (transaction.setUrl(payload.redirect), data.request = this._.ajax.createRequest(payload.redirect), this._dispatch(transaction, data)) : void(document.location.href = payload.redirect) : data.request
            }.bind(this))
        }
    });
    _context.register(AjaxAgent, "AjaxAgent")
}, {Arrays: "Utils.Arrays"}), _context.invoke("Nittro.Page", function (Arrays, DOM, Url) {
    var HistoryAgent = _context.extend(function (page, history, options) {
        this._ = {
            page: page,
            history: history,
            options: Arrays.mergeTree({}, HistoryAgent.defaults, options)
        }, this._.page.on("transaction-created", this._initTransaction.bind(this))
    }, {
        STATIC: {defaults: {whitelistHistory: !1}}, _initTransaction: function (evt) {
            "history" in evt.data.context ? evt.data.transaction.setIsHistoryState(evt.data.context.history) : evt.data.context.element ? evt.data.transaction.setIsHistoryState(DOM.getData(evt.data.context.element, "history", !this._.options.whitelistHistory)) : evt.data.transaction.setIsHistoryState(!this._.options.whitelistHistory);
            var data = {title: document.title};
            evt.data.transaction.on("dispatch", this._dispatch.bind(this, data)), evt.data.transaction.on("ajax-response", this._handleResponse.bind(this, data))
        }, _dispatch: function (data, evt) {
            evt.target.then(this._saveState.bind(this, evt.target, data), function () {
            })
        }, _handleResponse: function (data, evt) {
            var payload = evt.data.response.getPayload();
            payload.title && (data.title = payload.title)
        }, _saveState: function (transaction, data) {
            transaction.getUrl().getOrigin() !== Url.fromCurrent().getOrigin() || transaction.isBackground() ? transaction.setIsHistoryState(!1) : transaction.isHistoryState() && this._.history.push(transaction.getUrl().toAbsolute(), data.title), data.title && (document.title = data.title)
        }
    });
    _context.register(HistoryAgent, "HistoryAgent")
}, {Arrays: "Utils.Arrays", DOM: "Utils.DOM", Url: "Utils.Url"}), _context.invoke("Nittro.Page", function (DOM) {
    var location = window.history.location || window.location, History = _context.extend("Nittro.Object", function () {
        History.Super.call(this), DOM.addListener(window, "popstate", this._handleState.bind(this))
    }, {
        push: function (url, title, data) {
            data || (data = {}), this.trigger("before-savestate", data), window.history.pushState({
                _nittro: !0,
                data: data
            }, title || document.title, url), title && (document.title = title), this.trigger("savestate", {
                title: title,
                url: url,
                data: data
            })
        }, replace: function (url, title, data) {
            data || (data = {}), this.trigger("before-savestate", data), window.history.replaceState({
                _nittro: !0,
                data: data
            }, title || document.title, url), title && (document.title = title), this.trigger("savestate", {
                title: title,
                url: url,
                data: data,
                replace: !0
            })
        }, _handleState: function (evt) {
            evt.state && evt.state._nittro && this.trigger("popstate", {
                title: document.title,
                url: location.href,
                data: evt.state.data
            })
        }
    });
    _context.register(History, "History")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Page", function (DOM, Arrays, CSSTransitions, undefined) {
    var TransitionAgent = _context.extend("Nittro.Object", function (page, options) {
        TransitionAgent.Super.call(this), this._.page = page, this._.options = Arrays.mergeTree({}, TransitionAgent.defaults, options), this._.ready = !0, this._.queue = [], this._.page.on("transaction-created", this._initTransaction.bind(this))
    }, {
        STATIC: {defaults: {defaultSelector: ".nittro-transition-auto"}}, _initTransaction: function (evt) {
            var data = {
                elements: this._getTransitionTargets(evt.data.context),
                removeTargets: this._getRemoveTargets(evt.data.context)
            };
            evt.data.transaction.on("dispatch", this._dispatch.bind(this, data)), evt.data.transaction.on("abort", this._abort.bind(this, data)), evt.data.transaction.on("snippets-apply", this._handleSnippets.bind(this, data))
        }, _dispatch: function (data, evt) {
            evt.target.then(this._transitionIn.bind(this, data, !1), this._transitionIn.bind(this, data, !0)), (data.elements.length || data.removeTargets.length) && (DOM.addClass(data.removeTargets, "nittro-dynamic-remove"), data.transitionOut = this._transitionOut(data), evt.waitFor(data.transitionOut))
        }, _abort: function (data) {
            (data.elements.length || data.removeTargets.length) && this._transitionIn(data, !0)
        }, _handleSnippets: function (data, evt) {
            var id, changeset = evt.data.changeset;
            for (id in changeset.add) changeset.add.hasOwnProperty(id) && (DOM.addClass(changeset.add[id].content, "nittro-dynamic-add", "nittro-transition-middle"), data.elements.push(changeset.add[id].content));
            data.transitionOut && evt.waitFor(data.transitionOut)
        }, _transitionOut: function (data) {
            return this._enqueue(data.elements.concat(data.removeTargets), "out")
        }, _transitionIn: function (data, aborting) {
            var elements = aborting ? data.elements.concat(data.removeTargets) : data.elements;
            if (elements.length) return this._enqueue(elements, "in").then(function () {
                DOM.removeClass(elements, "nittro-dynamic-add", "nittro-dynamic-remove")
            })
        }, _enqueue: function (elements, dir) {
            return this._.ready ? (this._.ready = !1, this._transition(elements, dir)) : new Promise(function (fulfill) {
                this._.queue.push([elements, dir, fulfill])
            }.bind(this))
        }, _transition: function (elements, dir) {
            return CSSTransitions.run(elements, {
                add: "nittro-transition-active nittro-transition-" + dir,
                remove: "nittro-transition-middle",
                after: "out" === dir ? "nittro-transition-middle" : null
            }, "in" === dir).then(function () {
                if (this._.queue.length) {
                    var q = this._.queue.shift();
                    this._transition(q[0], q[1]).then(function () {
                        q[2](q[0])
                    })
                } else this._.ready = !0
            }.bind(this))
        }, _getTransitionTargets: function (context) {
            var sel, targets;
            return sel = context.transition !== undefined ? context.transition : context.element ? DOM.getData(context.element, "transition") : undefined, sel !== undefined || context.element && DOM.getData(context.element, "dynamic-remove") || (sel = this._.options.defaultSelector), targets = sel ? DOM.find(sel) : [], this.trigger("prepare-transition-targets", {
                element: context.element,
                targets: targets
            }), targets
        }, _getRemoveTargets: function (context) {
            if (!context.element) return [];
            var sel = DOM.getData(context.element, "dynamic-remove"), targets = sel ? DOM.find(sel) : [];
            return targets.length && this.trigger("prepare-remove-targets", {targets: targets.slice()}), targets
        }
    });
    _context.register(TransitionAgent, "TransitionAgent")
}, {
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays",
    CSSTransitions: "Utils.CSSTransitions"
}), _context.invoke("Nittro.Page", function (Url) {
    var Transaction = _context.extend("Nittro.Object", function (url) {
        Transaction.Super.call(this), this._.url = Url.from(url), this._.history = !0, this._.background = !1, this._.promise = new Promise(function (fulfill, reject) {
            this._.fulfill = fulfill, this._.reject = reject
        }.bind(this))
    }, {
        STATIC: {
            createRejected: function (url, reason) {
                var self = new Transaction(url);
                return self._.reject(reason), self
            }
        }, getUrl: function () {
            return this._.url
        }, setUrl: function (url) {
            return this._.url = Url.from(url), this
        }, isHistoryState: function () {
            return this._.history
        }, setIsHistoryState: function (value) {
            return this._.history = value, this
        }, isBackground: function () {
            return this._.background
        }, setIsBackground: function (value) {
            return this._.background = value, this
        }, dispatch: function () {
            return this.trigger("dispatch").then(this._.fulfill, this._.reject), this
        }, abort: function () {
            return this._.reject({type: "abort"}), this.trigger("abort"), this
        }, then: function (onfulfilled, onrejected) {
            return this._.promise.then(onfulfilled, onrejected)
        }
    });
    _context.register(Transaction, "Transaction")
}, {Url: "Utils.Url"}), _context.invoke("Nittro.Page", function () {
    var CspAgent = _context.extend(function (page, nonce) {
        this._ = {page: page, nonce: nonce}, this._.page.on("transaction-created", this._initTransaction.bind(this))
    }, {
        _initTransaction: function (evt) {
            var data = {nonce: null, pending: null};
            evt.data.transaction.on("ajax-response", this._handleResponse.bind(this, data)), evt.data.transaction.on("snippets-apply", this._handleSnippets.bind(this, data))
        }, _handleResponse: function (data, evt) {
            var m = /(?:^|;\s*)script-src\s[^;]*'nonce-([^']+)'/.exec(evt.data.response.getHeader("Content-Security-Policy") || evt.data.response.getHeader("Content-Security-Policy-Report-Only") || "");
            m ? data.nonce = m[1] : data.nonce = !1, data.pending && data.pending()
        }, _handleSnippets: function (data, evt) {
            null !== data.nonce ? this._handleChangeset(evt.data.changeset, data.nonce) : evt.waitFor(this._scheduleHandleChangeset(evt.data.changeset, data))
        }, _scheduleHandleChangeset: function (changeset, data) {
            return new Promise(function (fulfill) {
                data.pending = function () {
                    this._handleChangeset(changeset, data.nonce), fulfill()
                }.bind(this)
            }.bind(this))
        }, _handleChangeset: function (changeset, nonce) {
            if (nonce) {
                var id;
                for (id in changeset.add) changeset.add.hasOwnProperty(id) && this._fixNonce(changeset.add[id].content, nonce);
                for (id in changeset.update) changeset.update.hasOwnProperty(id) && this._fixNonce(changeset.update[id].content, nonce)
            }
        }, _fixNonce: function (elem, nonce) {
            var i, scripts = elem.getElementsByTagName("script");
            for (i = 0; i < scripts.length; i++) scripts.item(i).getAttribute("nonce") === nonce && scripts.item(i).setAttribute("nonce", this._.nonce || "")
        }
    });
    _context.register(CspAgent, "CspAgent")
}), _context.invoke("Nittro.Page", function (Url) {
    var GoogleAnalyticsHelper = _context.extend(function (history) {
        this._ = {history: history}, this._.history.on("savestate popstate", this._handleState.bind(this))
    }, {
        _handleState: function (evt) {
            "function" != typeof window.ga || evt.data.replace || (window.ga("set", {
                page: Url.from(evt.data.url).setHash(null).toLocal(),
                title: evt.data.title
            }), window.ga("send", "pageview"))
        }
    });
    _context.register(GoogleAnalyticsHelper, "GoogleAnalyticsHelper")
}, {Url: "Utils.Url"}), _context.invoke("Nittro.Page", function (Transaction, DOM, Arrays, Url) {
    var Service = _context.extend("Nittro.Object", function (snippetManager, history, options) {
        Service.Super.call(this), this._.snippetManager = snippetManager, this._.history = history, this._.options = Arrays.mergeTree({}, Service.defaults, options), this._.setup = !1, this._.currentTransaction = null, this._.currentUrl = Url.fromCurrent(), this._.history.on("popstate", this._handleState.bind(this)), DOM.addListener(document, "click", this._handleLinkClick.bind(this)), this._checkReady()
    }, {
        STATIC: {defaults: {whitelistLinks: !1, backgroundErrors: !1}}, open: function (url, method, data, context) {
            try {
                context || (context = {}), context.method = method, context.data = data;
                var evt = this.trigger("before-transaction", {url: url, context: context});
                return evt.isDefaultPrevented() ? this._createRejectedTransaction(url, {type: "abort"}) : (context.event && context.event.preventDefault(), evt.then(function () {
                    return evt.isDefaultPrevented() ? this._createRejectedTransaction(url, {type: "abort"}) : this._createTransaction(url, context)
                }.bind(this)))
            } catch (e) {
                return this._createRejectedTransaction(url, e)
            }
        }, openLink: function (link, evt) {
            return this.open(link.href, "get", null, {event: evt, element: link})
        }, getSnippet: function (id) {
            return this._.snippetManager.getSnippet(id)
        }, isSnippet: function (elem) {
            return this._.snippetManager.isSnippet(elem)
        }, _handleState: function (evt) {
            if (this._checkUrl(null, this._.currentUrl)) {
                var url = Url.from(evt.data.url);
                this._.currentUrl = url, this.open(url, "get", null, {history: !1}).then(null, function () {
                    document.location.href = url.toAbsolute()
                })
            }
        }, _checkReady: function () {
            return "loading" === document.readyState ? void DOM.addListener(document, "readystatechange", this._checkReady.bind(this)) : void(this._.setup || (this._.setup = !0, window.setTimeout(function () {
                this._.history.replace((window.history.location || window.location).href), this._.snippetManager.setup()
            }.bind(this), 1)))
        }, _handleLinkClick: function (evt) {
            if (!(evt.defaultPrevented || evt.ctrlKey || evt.shiftKey || evt.altKey || evt.metaKey || evt.button > 0)) {
                var link = DOM.closest(evt.target, "a");
                link && this._checkLink(link) && this._checkUrl(link.href) && this.openLink(link, evt)
            }
        }, _createTransaction: function (url, context) {
            var transaction = new Transaction(url);
            return this._initTransaction(transaction, context), this.trigger("transaction-created", {
                transaction: transaction,
                context: context
            }), this._dispatchTransaction(transaction)
        }, _createRejectedTransaction: function (url, reason) {
            var transaction = Transaction.createRejected(url, reason);
            return transaction.then(null, this._handleError.bind(this, transaction))
        }, _initTransaction: function (transaction, context) {
            "background" in context ? transaction.setIsBackground(context.background) : context.element && transaction.setIsBackground(DOM.getData(context.element, "background", !1))
        }, _dispatchTransaction: function (transaction) {
            return transaction.isBackground() || (this._.currentTransaction && this._.currentTransaction.abort(), this._.currentTransaction = transaction), transaction.dispatch().then(this._handleSuccess.bind(this, transaction), this._handleError.bind(this, transaction))
        }, _checkUrl: function (url, current) {
            return url = url ? Url.from(url) : Url.fromCurrent(), current = current ? Url.from(current) : Url.fromCurrent(), url.compare(current) !== Url.PART.HASH
        }, _checkLink: function (link) {
            return !link.hasAttribute("target") && link.hasAttribute("href") && DOM.getData(link, "ajax", !this._.options.whitelistLinks)
        }, _handleSuccess: function (transaction) {
            transaction.isBackground() || (this._.currentTransaction = null), transaction.isHistoryState() && (this._.currentUrl = transaction.getUrl())
        }, _handleError: function (transaction, err) {
            transaction === this._.currentTransaction && (this._.currentTransaction = null), transaction.isBackground() && !this._.options.backgroundErrors || this.trigger("error", err)
        }
    });
    _context.register(Service, "Service")
}, {DOM: "Utils.DOM", Arrays: "Utils.Arrays", Url: "Utils.Url"}), function (global, factory) {
    if (global.JSON) if ("function" == typeof define && define.amd) define(function () {
        return factory(global)
    }); else if ("object" == typeof module && "object" == typeof module.exports) module.exports = factory(global); else {
        var init = !global.Nette || !global.Nette.noInit;
        global.Nette = factory(global), init && global.Nette.initOnLoad()
    }
}("undefined" != typeof window ? window : this, function (window) {
    "use strict";

    function getHandler(callback) {
        return function (e) {
            return callback.call(this, e)
        }
    }

    var Nette = {};
    return Nette.formErrors = [], Nette.version = "2.4", Nette.addEvent = function (element, on, callback) {
        element.addEventListener ? element.addEventListener(on, callback) : "DOMContentLoaded" === on ? element.attachEvent("onreadystatechange", function () {
            "complete" === element.readyState && callback.call(this)
        }) : element.attachEvent("on" + on, getHandler(callback))
    }, Nette.getValue = function (elem) {
        var i;
        if (elem) {
            if (elem.tagName) {
                if ("radio" === elem.type) {
                    var elements = elem.form.elements;
                    for (i = 0; i < elements.length; i++) if (elements[i].name === elem.name && elements[i].checked) return elements[i].value;
                    return null
                }
                if ("file" === elem.type) return elem.files || elem.value;
                if ("select" === elem.tagName.toLowerCase()) {
                    var index = elem.selectedIndex, options = elem.options, values = [];
                    if ("select-one" === elem.type) return index < 0 ? null : options[index].value;
                    for (i = 0; i < options.length; i++) options[i].selected && values.push(options[i].value);
                    return values
                }
                if (elem.name && elem.name.match(/\[\]$/)) {
                    var elements = elem.form.elements[elem.name].tagName ? [elem] : elem.form.elements[elem.name],
                        values = [];
                    for (i = 0; i < elements.length; i++) ("checkbox" !== elements[i].type || elements[i].checked) && values.push(elements[i].value);
                    return values
                }
                return "checkbox" === elem.type ? elem.checked : "textarea" === elem.tagName.toLowerCase() ? elem.value.replace("\r", "") : elem.value.replace("\r", "").replace(/^\s+|\s+$/g, "")
            }
            return elem[0] ? Nette.getValue(elem[0]) : null
        }
        return null
    }, Nette.getEffectiveValue = function (elem) {
        var val = Nette.getValue(elem);
        return elem.getAttribute && val === elem.getAttribute("data-nette-empty-value") && (val = ""), val
    }, Nette.validateControl = function (elem, rules, onlyCheck, value, emptyOptional) {
        elem = elem.tagName ? elem : elem[0], rules = rules || Nette.parseJSON(elem.getAttribute("data-nette-rules")), value = void 0 === value ? {value: Nette.getEffectiveValue(elem)} : value;
        for (var id = 0, len = rules.length; id < len; id++) {
            var rule = rules[id], op = rule.op.match(/(~)?([^?]+)/),
                curElem = rule.control ? elem.form.elements.namedItem(rule.control) : elem;
            if (rule.neg = op[1], rule.op = op[2], rule.condition = !!rule.rules, curElem) if ("optional" !== rule.op) {
                if (!emptyOptional || rule.condition || ":filled" === rule.op) {
                    curElem = curElem.tagName ? curElem : curElem[0];
                    var curValue = elem === curElem ? value : {value: Nette.getEffectiveValue(curElem)},
                        success = Nette.validateRule(curElem, rule.op, rule.arg, curValue);
                    if (null !== success) if (rule.neg && (success = !success), rule.condition && success) {
                        if (!Nette.validateControl(elem, rule.rules, onlyCheck, value, ":blank" !== rule.op && emptyOptional)) return !1
                    } else if (!rule.condition && !success) {
                        if (Nette.isDisabled(curElem)) continue;
                        if (!onlyCheck) {
                            var arr = Nette.isArray(rule.arg) ? rule.arg : [rule.arg],
                                message = rule.msg.replace(/%(value|\d+)/g, function (foo, m) {
                                    return Nette.getValue("value" === m ? curElem : elem.form.elements.namedItem(arr[m].control))
                                });
                            Nette.addError(curElem, message)
                        }
                        return !1
                    }
                }
            } else emptyOptional = !Nette.validateRule(elem, ":filled", null, value)
        }
        return !("number" === elem.type && !elem.validity.valid) || (onlyCheck || Nette.addError(elem, "Please enter a valid value."), !1)
    }, Nette.validateForm = function (sender, onlyCheck) {
        var form = sender.form || sender, scope = !1;
        if (Nette.formErrors = [], form["nette-submittedBy"] && null !== form["nette-submittedBy"].getAttribute("formnovalidate")) {
            var scopeArr = Nette.parseJSON(form["nette-submittedBy"].getAttribute("data-nette-validation-scope"));
            if (!scopeArr.length) return Nette.showFormErrors(form, []), !0;
            scope = new RegExp("^(" + scopeArr.join("-|") + "-)")
        }
        var i, elem, radios = {};
        for (i = 0; i < form.elements.length; i++) if (elem = form.elements[i], !elem.tagName || elem.tagName.toLowerCase() in {
                input: 1,
                select: 1,
                textarea: 1,
                button: 1
            }) {
            if ("radio" === elem.type) {
                if (radios[elem.name]) continue;
                radios[elem.name] = !0
            }
            if (!(scope && !elem.name.replace(/]\[|\[|]|$/g, "-").match(scope) || Nette.isDisabled(elem) || Nette.validateControl(elem, null, onlyCheck) || Nette.formErrors.length)) return !1
        }
        var success = !Nette.formErrors.length;
        return Nette.showFormErrors(form, Nette.formErrors), success
    }, Nette.isDisabled = function (elem) {
        if ("radio" === elem.type) {
            for (var i = 0, elements = elem.form.elements; i < elements.length; i++) if (elements[i].name === elem.name && !elements[i].disabled) return !1;
            return !0
        }
        return elem.disabled
    }, Nette.addError = function (elem, message) {
        Nette.formErrors.push({element: elem, message: message})
    }, Nette.showFormErrors = function (form, errors) {
        for (var focusElem, messages = [], i = 0; i < errors.length; i++) {
            var elem = errors[i].element, message = errors[i].message;
            Nette.inArray(messages, message) || (messages.push(message), !focusElem && elem.focus && (focusElem = elem))
        }
        messages.length && (alert(messages.join("\n")), focusElem && focusElem.focus())
    }, Nette.expandRuleArgument = function (form, arg) {
        if (arg && arg.control) {
            var control = form.elements.namedItem(arg.control), value = {value: Nette.getEffectiveValue(control)};
            Nette.validateControl(control, null, !0, value), arg = value.value
        }
        return arg
    }, Nette.validateRule = function (elem, op, arg, value) {
        value = void 0 === value ? {value: Nette.getEffectiveValue(elem)} : value, ":" === op.charAt(0) && (op = op.substr(1)), op = op.replace("::", "_"), op = op.replace(/\\/g, "");
        for (var arr = Nette.isArray(arg) ? arg.slice(0) : [arg], i = 0, len = arr.length; i < len; i++) arr[i] = Nette.expandRuleArgument(elem.form, arr[i]);
        return Nette.validators[op] ? Nette.validators[op](elem, Nette.isArray(arg) ? arr : arr[0], value.value, value) : null
    }, Nette.validators = {
        filled: function (elem, arg, val) {
            return !("number" !== elem.type || !elem.validity.badInput) || "" !== val && val !== !1 && null !== val && (!Nette.isArray(val) || !!val.length) && (!window.FileList || !(val instanceof window.FileList) || val.length)
        }, blank: function (elem, arg, val) {
            return !Nette.validators.filled(elem, arg, val)
        }, valid: function (elem, arg, val) {
            return Nette.validateControl(elem, null, !0)
        }, equal: function (elem, arg, val) {
            function toString(val) {
                return "number" == typeof val || "string" == typeof val ? "" + val : val === !0 ? "1" : ""
            }

            if (void 0 === arg) return null;
            val = Nette.isArray(val) ? val : [val], arg = Nette.isArray(arg) ? arg : [arg];
            loop:for (var i1 = 0, len1 = val.length; i1 < len1; i1++) {
                for (var i2 = 0, len2 = arg.length; i2 < len2; i2++) if (toString(val[i1]) === toString(arg[i2])) continue loop;
                return !1
            }
            return !0
        }, notEqual: function (elem, arg, val) {
            return void 0 === arg ? null : !Nette.validators.equal(elem, arg, val)
        }, minLength: function (elem, arg, val) {
            if ("number" === elem.type) {
                if (elem.validity.tooShort) return !1;
                if (elem.validity.badInput) return null
            }
            return val.length >= arg
        }, maxLength: function (elem, arg, val) {
            if ("number" === elem.type) {
                if (elem.validity.tooLong) return !1;
                if (elem.validity.badInput) return null
            }
            return val.length <= arg
        }, length: function (elem, arg, val) {
            if ("number" === elem.type) {
                if (elem.validity.tooShort || elem.validity.tooLong) return !1;
                if (elem.validity.badInput) return null
            }
            return arg = Nette.isArray(arg) ? arg : [arg, arg], (null === arg[0] || val.length >= arg[0]) && (null === arg[1] || val.length <= arg[1])
        }, email: function (elem, arg, val) {
            return /^("([ !#-[\]-~]|\\[ -~])+"|[-a-z0-9!#$%&'*+\/=?^_`{|}~]+(\.[-a-z0-9!#$%&'*+\/=?^_`{|}~]+)*)@([0-9a-z\u00C0-\u02FF\u0370-\u1EFF]([-0-9a-z\u00C0-\u02FF\u0370-\u1EFF]{0,61}[0-9a-z\u00C0-\u02FF\u0370-\u1EFF])?\.)+[a-z\u00C0-\u02FF\u0370-\u1EFF]([-0-9a-z\u00C0-\u02FF\u0370-\u1EFF]{0,17}[a-z\u00C0-\u02FF\u0370-\u1EFF])?$/i.test(val)
        }, url: function (elem, arg, val, value) {
            return /^[a-z\d+.-]+:/.test(val) || (val = "http://" + val), !!/^https?:\/\/((([-_0-9a-z\u00C0-\u02FF\u0370-\u1EFF]+\.)*[0-9a-z\u00C0-\u02FF\u0370-\u1EFF]([-0-9a-z\u00C0-\u02FF\u0370-\u1EFF]{0,61}[0-9a-z\u00C0-\u02FF\u0370-\u1EFF])?\.)?[a-z\u00C0-\u02FF\u0370-\u1EFF]([-0-9a-z\u00C0-\u02FF\u0370-\u1EFF]{0,17}[a-z\u00C0-\u02FF\u0370-\u1EFF])?|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|\[[0-9a-f:]{3,39}\])(:\d{1,5})?(\/\S*)?$/i.test(val) && (value.value = val, !0)
        }, regexp: function (elem, arg, val) {
            var parts = "string" == typeof arg && arg.match(/^\/(.*)\/([imu]*)$/);
            try {
                return parts && new RegExp(parts[1], parts[2].replace("u", "")).test(val)
            } catch (e) {
            }
        }, pattern: function (elem, arg, val) {
            try {
                return "string" == typeof arg ? new RegExp("^(?:" + arg + ")$").test(val) : null
            } catch (e) {
            }
        }, integer: function (elem, arg, val) {
            return ("number" !== elem.type || !elem.validity.badInput) && /^-?[0-9]+$/.test(val)
        }, "float": function (elem, arg, val, value) {
            return ("number" !== elem.type || !elem.validity.badInput) && (val = val.replace(" ", "").replace(",", "."), !!/^-?[0-9]*[.,]?[0-9]+$/.test(val) && (value.value = val, !0))
        }, min: function (elem, arg, val) {
            if ("number" === elem.type) {
                if (elem.validity.rangeUnderflow) return !1;
                if (elem.validity.badInput) return null
            }
            return null === arg || parseFloat(val) >= arg
        }, max: function (elem, arg, val) {
            if ("number" === elem.type) {
                if (elem.validity.rangeOverflow) return !1;
                if (elem.validity.badInput) return null
            }
            return null === arg || parseFloat(val) <= arg
        }, range: function (elem, arg, val) {
            if ("number" === elem.type) {
                if (elem.validity.rangeUnderflow || elem.validity.rangeOverflow) return !1;
                if (elem.validity.badInput) return null
            }
            return Nette.isArray(arg) ? (null === arg[0] || parseFloat(val) >= arg[0]) && (null === arg[1] || parseFloat(val) <= arg[1]) : null
        }, submitted: function (elem, arg, val) {
            return elem.form["nette-submittedBy"] === elem
        }, fileSize: function (elem, arg, val) {
            if (window.FileList) for (var i = 0; i < val.length; i++) if (val[i].size > arg) return !1;
            return !0
        }, image: function (elem, arg, val) {
            if (window.FileList && val instanceof window.FileList) for (var i = 0; i < val.length; i++) {
                var type = val[i].type;
                if (type && "image/gif" !== type && "image/png" !== type && "image/jpeg" !== type) return !1
            }
            return !0
        }
    }, Nette.toggleForm = function (form, elem) {
        var i;
        for (Nette.toggles = {}, i = 0; i < form.elements.length; i++) form.elements[i].tagName.toLowerCase() in {
            input: 1,
            select: 1,
            textarea: 1,
            button: 1
        } && Nette.toggleControl(form.elements[i], null, null, !elem);
        for (i in Nette.toggles) Nette.toggle(i, Nette.toggles[i], elem)
    }, Nette.toggleControl = function (elem, rules, success, firsttime, value) {
        rules = rules || Nette.parseJSON(elem.getAttribute("data-nette-rules")),
            value = void 0 === value ? {value: Nette.getEffectiveValue(elem)} : value;
        for (var curSuccess, has = !1, handled = [], handler = function () {
            Nette.toggleForm(elem.form, elem)
        }, id = 0, len = rules.length; id < len; id++) {
            var rule = rules[id], op = rule.op.match(/(~)?([^?]+)/),
                curElem = rule.control ? elem.form.elements.namedItem(rule.control) : elem;
            if (curElem) {
                if (curSuccess = success, success !== !1) {
                    rule.neg = op[1], rule.op = op[2];
                    var curValue = elem === curElem ? value : {value: Nette.getEffectiveValue(curElem)};
                    if (curSuccess = Nette.validateRule(curElem, rule.op, rule.arg, curValue), null === curSuccess) continue;
                    rule.neg && (curSuccess = !curSuccess), rule.rules || (success = curSuccess)
                }
                if (rule.rules && Nette.toggleControl(elem, rule.rules, curSuccess, firsttime, value) || rule.toggle) {
                    if (has = !0, firsttime) for (var oldIE = !document.addEventListener, name = curElem.tagName ? curElem.name : curElem[0].name, els = curElem.tagName ? curElem.form.elements : curElem, i = 0; i < els.length; i++) els[i].name !== name || Nette.inArray(handled, els[i]) || (Nette.addEvent(els[i], oldIE && els[i].type in {
                        checkbox: 1,
                        radio: 1
                    } ? "click" : "change", handler), handled.push(els[i]));
                    for (var id2 in rule.toggle || []) Object.prototype.hasOwnProperty.call(rule.toggle, id2) && (Nette.toggles[id2] = Nette.toggles[id2] || (rule.toggle[id2] ? curSuccess : !curSuccess))
                }
            }
        }
        return has
    }, Nette.parseJSON = function (s) {
        return "{op" === (s || "").substr(0, 3) ? eval("[" + s + "]") : JSON.parse(s || "[]")
    }, Nette.toggle = function (id, visible, srcElement) {
        var elem = document.getElementById(id);
        elem && (elem.style.display = visible ? "" : "none")
    }, Nette.initForm = function (form) {
        Nette.toggleForm(form), form.noValidate || (form.noValidate = !0, Nette.addEvent(form, "submit", function (e) {
            Nette.validateForm(form) || (e && e.stopPropagation ? (e.stopPropagation(), e.preventDefault()) : window.event && (event.cancelBubble = !0, event.returnValue = !1))
        }))
    }, Nette.initOnLoad = function () {
        Nette.addEvent(document, "DOMContentLoaded", function () {
            for (var i = 0; i < document.forms.length; i++) for (var form = document.forms[i], j = 0; j < form.elements.length; j++) if (form.elements[j].getAttribute("data-nette-rules")) {
                Nette.initForm(form);
                break
            }
            Nette.addEvent(document.body, "click", function (e) {
                for (var target = e.target || e.srcElement; target;) {
                    if (target.form && target.type in {submit: 1, image: 1}) {
                        target.form["nette-submittedBy"] = target;
                        break
                    }
                    target = target.parentNode
                }
            })
        })
    }, Nette.isArray = function (arg) {
        return "[object Array]" === Object.prototype.toString.call(arg)
    }, Nette.inArray = function (arr, val) {
        if ([].indexOf) return arr.indexOf(val) > -1;
        for (var i = 0; i < arr.length; i++) if (arr[i] === val) return !0;
        return !1
    }, Nette.webalize = function (s) {
        s = s.toLowerCase();
        var i, ch, res = "";
        for (i = 0; i < s.length; i++) ch = Nette.webalizeTable[s.charAt(i)], res += ch ? ch : s.charAt(i);
        return res.replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "")
    }, Nette.webalizeTable = {
        "á": "a",
        "ä": "a",
        "č": "c",
        "ď": "d",
        "é": "e",
        "ě": "e",
        "í": "i",
        "ľ": "l",
        "ň": "n",
        "ó": "o",
        "ô": "o",
        "ř": "r",
        "š": "s",
        "ť": "t",
        "ú": "u",
        "ů": "u",
        "ý": "y",
        "ž": "z"
    }, Nette
}), _context.invoke("Nittro.Forms", function () {
    if (!window.Nette || !window.Nette.validators) throw new Error("netteForms.js asset from Nette/Forms has not been loaded");
    _context.register(window.Nette, "Vendor")
}), _context.invoke("Nittro.Forms", function (undefined) {
    var FormData = _context.extend(function () {
        this._ = {dataStorage: [], upload: !1}
    }, {
        append: function (name, value) {
            if (value === undefined || null === value) return this;
            if (this._isFile(value)) this._.upload = !0; else {
                if ("object" == typeof value && "valueOf" in value && /string|number|boolean/.test(typeof value.valueOf()) && !arguments[2]) return this.append(name, value.valueOf(), !0);
                if (!/string|number|boolean/.test(typeof value)) throw new Error("Only scalar values and File/Blob objects can be appended to FormData, " + typeof value + " given")
            }
            return this._.dataStorage.push({name: name, value: value}), this
        }, isUpload: function () {
            return this._.upload
        }, _isFile: function (value) {
            return window.File !== undefined && value instanceof window.File || window.Blob !== undefined && value instanceof window.Blob
        }, mergeData: function (data) {
            for (var i = 0; i < data.length; i++) this.append(data[i].name, data[i].value);
            return this
        }, exportData: function (forcePlain) {
            if (!forcePlain && this.isUpload() && window.FormData !== undefined) {
                var i, fd = new window.FormData;
                for (i = 0; i < this._.dataStorage.length; i++) "boolean" == typeof this._.dataStorage[i].value ? fd.append(this._.dataStorage[i].name, this._.dataStorage[i].value ? 1 : 0) : fd.append(this._.dataStorage[i].name, this._.dataStorage[i].value);
                return fd
            }
            return this._.dataStorage.filter(function (e) {
                return !this._isFile(e.value)
            }, this)
        }
    });
    _context.register(FormData, "FormData")
}), _context.invoke("Nittro.Forms", function (DOM, Arrays, DateTime, FormData, Vendor, undefined) {
    var FileList = window.FileList || function () {
    }, Form = _context.extend("Nittro.Object", function (form) {
        Form.Super.call(this), this._.submittedBy = null, this._.inLiveValidation = !1, this._handleSubmit = this._handleSubmit.bind(this), this._handleReset = this._handleReset.bind(this), this.setElement(form), this.on("error:default", this._handleError.bind(this)), this.on("blur:default", this._handleBlur.bind(this))
    }, {
        setElement: function (form) {
            if ("string" == typeof form && (form = DOM.getById(form)), !(form && form instanceof HTMLFormElement)) throw new TypeError("Invalid argument, must be a HTMLFormElement");
            return this._.form = form, this._.form.noValidate = "novalidate", this._.validationMode = DOM.getData(form, "validation-mode"), this._.submittedBy && (this._.form["nette-submittedBy"] = this.getElement(this._.submittedBy)), DOM.addListener(this._.form, "submit", this._handleSubmit), DOM.addListener(this._.form, "reset", this._handleReset), this
        }, getElement: function (name) {
            return name ? this._.form.elements.namedItem(name) : this._.form
        }, getElements: function () {
            return this._.form.elements
        }, setSubmittedBy: function (value) {
            return value ? (this._.submittedBy = value, this._.form["nette-submittedBy"] = this.getElement(value)) : this._.submittedBy = this._.form["nette-submittedBy"] = null, this
        }, validate: function (sender) {
            for (var container, i = 0, names = this._getFieldNames(); i < names.length; i++) container = this._getErrorContainer(this.getElement(names[i])), container && DOM.getByClassName("error", container).forEach(function (elem) {
                elem.parentNode.removeChild(elem)
            });
            if (!Vendor.validateForm(sender || this._.form)) return !1;
            var evt = this.trigger("validate", {sender: sender});
            return !evt.isDefaultPrevented()
        }, setValues: function (values, reset) {
            var name, value, i, names = this._getFieldNames();
            for (values || (values = {}), i = 0; i < names.length; i++) {
                if (name = names[i], value = undefined, name.indexOf("[") > -1 ? (value = values, name.replace(/]/g, "").split(/\[/g).some(function (key) {
                        return "" === key || (key in value ? (value = value[key], !1) : (value = undefined, !0))
                    })) : name in values && (value = values[name]), value === undefined) {
                    if (!reset) continue;
                    value = null
                }
                this.setValue(name, value)
            }
        }, setValue: function (elem, value) {
            "string" == typeof elem && (elem = this._.form.elements.namedItem(elem));
            var i, toStr = function (v) {
                return "" + v
            };
            if (!elem) throw new TypeError("Invalid argument to setValue(), must be (the name of) an existing form element");
            if (elem.tagName) if ("radio" === elem.type) elem.checked = null !== value && elem.value === toStr(value); else if ("file" === elem.type) null === value && (value = elem.parentNode.innerHTML, DOM.html(elem.parentNode, value)); else if ("select" === elem.tagName.toLowerCase()) {
                var v, single = "select-one" === elem.type, arr = Array.isArray(value);
                for (value = arr ? value.map(toStr) : toStr(value), i = 0; i < elem.options.length && (v = arr ? value.indexOf(elem.options.item(i).value) > -1 : value === elem.options.item(i).value, elem.options.item(i).selected = v, !v || !single); i++) ;
            } else "checkbox" === elem.type ? elem.checked = Array.isArray(value) ? value.map(toStr).indexOf(elem.value) > -1 : !!value : "date" === elem.type ? elem.value = value ? DateTime.from(value).format("Y-m-d") : "" : "datetime-local" === elem.type || "datetime" === elem.type ? elem.value = value ? DateTime.from(value).format("Y-m-d\\TH:i:s") : "" : elem.value = null !== value ? toStr(value) : ""; else if ("length" in elem) for (i = 0; i < elem.length; i++) this.setValue(elem[i], value);
            return this
        }, getValue: function (name) {
            return Vendor.getEffectiveValue(this.getElement(name))
        }, serialize: function () {
            var elem, i, value, data = new FormData, names = this._getFieldNames(!0);
            for (this._.submittedBy && names.push(this._.submittedBy), i = 0; i < names.length; i++) if (elem = this._.form.elements.namedItem(names[i]), value = Vendor.getEffectiveValue(elem), Array.isArray(value) || value instanceof FileList) for (var j = 0; j < value.length; j++) data.append(names[i], value[j]); else data.append(names[i], value);
            return this.trigger("serialize", data), data
        }, submit: function (by) {
            if (by) {
                var btn = this._.form.elements.namedItem(by);
                if (!btn || "submit" !== btn.type) throw new TypeError("Unknown element or not a submit button: " + by);
                DOM.trigger(btn, "click")
            } else DOM.trigger(this._.form, "submit");
            return this
        }, reset: function () {
            return this._.form.reset(), this
        }, destroy: function () {
            this.trigger("destroy"), this.off(), DOM.removeListener(this._.form, "submit", this._handleSubmit), DOM.removeListener(this._.form, "reset", this._handleReset), this._.form = null
        }, _handleSubmit: function (evt) {
            if (this.trigger("submit").isDefaultPrevented()) return void evt.preventDefault();
            var sender = this._.submittedBy ? this.getElement(this._.submittedBy) : null;
            this.validate(sender) || evt.preventDefault()
        }, _handleReset: function (evt) {
            if (evt.target === this._.form) {
                var elem, i;
                for (i = 0; i < this._.form.elements.length; i++) elem = this._.form.elements.item(i), "hidden" === elem.type && elem.hasAttribute("data-default-value") ? this.setValue(elem, DOM.getData(elem, "default-value")) : "file" === elem.type && this.setValue(elem, null);
                this._.submittedBy = this._.form["nette-submittedBy"] = null, this.trigger("reset")
            }
        }, _handleError: function (evt) {
            var elem, container = this._getErrorContainer(evt.data.element);
            !this._.inLiveValidation && evt.data.element && "function" == typeof evt.data.element.focus && evt.data.element.focus(), container && (elem = evt.data.element && evt.data.element.parentNode === container ? DOM.create("span", {"class": "error"}) : DOM.create(container.tagName.match(/^(ul|ol)$/i) ? "li" : "p", {"class": "error"}), elem.textContent = evt.data.message, container.appendChild(elem))
        }, _handleBlur: function (evt) {
            var container = this._getErrorContainer(evt.data.element);
            container && DOM.getByClassName("error", container).forEach(function (elem) {
                elem.parentNode.removeChild(elem)
            }), "live" === DOM.getData(evt.data.element, "validation-mode", this._.validationMode) && (this._.inLiveValidation = !0, Vendor.validateControl(evt.data.element), this._.inLiveValidation = !1)
        }, _getFieldNames: function (enabledOnly) {
            var elem, i, names = [];
            for (i = 0; i < this._.form.elements.length; i++) elem = this._.form.elements.item(i), !elem.name || enabledOnly && elem.disabled || names.indexOf(elem.name) !== -1 || elem.type in {
                submit: 1,
                button: 1,
                reset: 1
            } || names.push(elem.name);
            return names
        }, _getErrorContainer: function (elem) {
            var container = elem && elem.id ? DOM.getById(elem.id + "-errors") : null;
            return container || DOM.getById(this._.form.id + "-errors") || (elem ? elem.parentNode : null)
        }
    });
    _context.register(Form, "Form")
}, {
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays",
    DateTime: "Utils.DateTime"
}), _context.invoke("Nittro.Forms", function (Form, Vendor, DOM, Arrays) {
    var Locator = _context.extend("Nittro.Object", function () {
        this._ = {
            registry: {},
            anonId: 0
        }, Vendor.addError = this._forwardError.bind(this), DOM.addListener(document, "blur", this._handleBlur.bind(this), !0)
    }, {
        getForm: function (id) {
            var elem;
            return "string" != typeof id && (elem = id, elem.getAttribute("id") || elem.setAttribute("id", "frm-anonymous" + ++this._.anonId), id = elem.getAttribute("id")), id in this._.registry || (this._.registry[id] = new Form(elem || id), this.trigger("form-added", {form: this._.registry[id]})), this._.registry[id]
        }, removeForm: function (id) {
            "string" != typeof id && (id = id.getAttribute("id")), id in this._.registry && (this.trigger("form-removed", {form: this._.registry[id]}), this._.registry[id].destroy(), delete this._.registry[id])
        }, refreshForms: function () {
            var elem, id;
            for (id in this._.registry) this._.registry.hasOwnProperty(id) && (elem = DOM.getById(id), elem ? elem !== this._.registry[id].getElement() && this._.registry[id].setElement(elem) : this.removeForm(id))
        }, _forwardError: function (elem, msg) {
            this.getForm(elem.form).trigger("error", {element: elem, message: msg})
        }, _handleBlur: function (evt) {
            evt.target.form && evt.target.form instanceof HTMLFormElement && this.getForm(evt.target.form).trigger("blur", {element: evt.target})
        }
    });
    _context.register(Locator, "Locator")
}, {DOM: "Utils.DOM", Arrays: "Utils.Arrays"}), _context.invoke("Nittro.Flashes", function (DOM) {
    var Helpers = {
        hasFixedParent: function (elem) {
            do {
                if ("fixed" === DOM.getStyle(elem, "position", !1)) return !0;
                elem = elem.offsetParent
            } while (elem && elem !== document.documentElement && elem !== document.body);
            return !1
        }, getRect: function (elem) {
            var rect = elem.getBoundingClientRect();
            return {
                left: rect.left,
                top: rect.top,
                right: rect.right,
                bottom: rect.bottom,
                width: "width" in rect ? rect.width : rect.right - rect.left,
                height: "height" in rect ? rect.height : rect.bottom - rect.top
            }
        }, tryFloatingPosition: function (elem, target, placement, positioner) {
            DOM.addClass(elem, "nittro-flash-floating"), DOM.setStyle(elem, {position: "absolute", opacity: 0});
            var position, fixed = Helpers.hasFixedParent(target), elemRect = Helpers.getRect(elem),
                targetRect = Helpers.getRect(target), style = {}, order = positioner.getDefaultOrder(), force = !1;
            if (fixed && (style.position = "fixed"), placement) {
                var m = placement.match(/^(.+?)(!)?(!)?$/);
                if (!positioner.supports(m[1])) throw new Error("Placement '" + m[1] + "' isn't supported");
                force = !!m[3], order = m[2] ? [m[1]] : [m[1]].concat(order)
            }
            for (var i = 0; i < order.length && (placement = order[i], !(position = positioner[placement].call(positioner, targetRect, elemRect, force))); i++) ;
            return position ? (style.left = position.left, style.top = position.top, fixed || (style.left += window.pageXOffset, style.top += window.pageYOffset), style.left += "px", style.top += "px", style.opacity = "", DOM.setStyle(elem, style), placement) : (DOM.removeClass(elem, "nittro-flash-floating"), DOM.setStyle(elem, {
                position: "",
                opacity: ""
            }), null)
        }
    };
    _context.register(Helpers, "Helpers")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Flashes", function (DOM, Arrays, CSSTransitions, Helpers) {
    var Message = _context.extend("Nittro.Object", function (service, content, options) {
        if (Message.Super.call(this), this._doDismiss = this._doDismiss.bind(this), this._.service = service, this._.options = Arrays.mergeTree({}, Message.defaults, options), this._.visible = !1, null === this._.service) return this._.elem = content, void this._scheduleDismiss();
        var target = this._getTarget(), tag = "div";
        target ? (null === this._.options.classes && (this._.options.classes = DOM.getData(target, "flash-classes")), null === this._.options.inline && (this._.options.inline = DOM.getData(target, "flash-inline")), this._.options.inline && (tag = target.tagName.match(/^(?:ul|ol)$/i) ? "li" : "p")) : this._.options.inline = !1, this._.elem = DOM.create(tag, {
            "class": "nittro-flash nittro-flash-" + this._.options.type,
            "data-flash-dynamic": "true"
        }), this._.options.classes && DOM.addClass(this._.elem, this._.options.classes.replace(/%type%/g, this._.options.type)), this._.options.rich ? DOM.html(this._.elem, content) : (DOM.addClass(this._.elem, "nittro-flash-plain"), this._.elem.textContent = content), this._.options.dismiss !== !1 && "number" != typeof this._.options.dismiss && (this._.options.dismiss = Math.max(5e3, Math.round(this._.elem.textContent.split(/\s+/).length / .003)))
    }, {
        STATIC: {
            wrap: function (elem) {
                return new Message(null, elem)
            },
            defaults: {
                type: "info",
                target: null,
                classes: null,
                inline: null,
                placement: null,
                rich: !1,
                dismiss: null
            }
        }, getElement: function () {
            return this._.elem
        }, show: function () {
            if (this._.visible !== !1) return Promise.resolve(this);
            this._.visible = null;
            var target = this._getTarget();
            if (target) {
                if (this._.options.inline) return target.appendChild(this._.elem), this._show("inline");
                this._.service.getLayer().appendChild(this._.elem);
                var placement = Helpers.tryFloatingPosition(this._.elem, target, this._.options.placement, this._.service.getPositioner());
                if (placement) return this._show(placement)
            }
            return this._.service.getGlobalHolder().appendChild(this._.elem), this._show("global")
        }, hide: function () {
            return this._.visible !== !0 ? Promise.resolve(this) : (this._.visible = null, DOM.addClass(this._.elem, "nittro-flash-hide"), this.trigger("hide"), CSSTransitions.run(this._.elem).then(function () {
                return this._.visible = !1, this._.elem.parentNode.removeChild(this._.elem), DOM.removeClass(this._.elem, "nittro-flash-hide"), this.trigger("hidden"), this
            }.bind(this)))
        }, dismiss: function () {
            return this.hide().then(function () {
                this.off(), this._.elem = this._.options = this._.service = null
            }.bind(this))
        }, _show: function (placement) {
            return DOM.toggleClass(this._.elem, "nittro-flash-prepare nittro-flash-" + placement, !0), this.trigger("show"), this.one("hidden", function () {
                DOM.toggleClass(this._.elem, "nittro-flash-" + placement, !1)
            }), CSSTransitions.run(this._.elem, {remove: "nittro-flash-prepare"}, !0).then(function () {
                return this._.visible = !0, this.trigger("shown"), this._scheduleDismiss(), this
            }.bind(this))
        }, _scheduleDismiss: function () {
            this._.options.dismiss !== !1 && (DOM.addListener(document, "mousemove", this._doDismiss), DOM.addListener(document, "mousedown", this._doDismiss), DOM.addListener(document, "keydown", this._doDismiss), DOM.addListener(document, "touchstart", this._doDismiss))
        }, _doDismiss: function () {
            DOM.removeListener(document, "mousemove", this._doDismiss), DOM.removeListener(document, "mousedown", this._doDismiss), DOM.removeListener(document, "keydown", this._doDismiss), DOM.removeListener(document, "touchstart", this._doDismiss), window.setTimeout(this.dismiss.bind(this), this._.options.dismiss)
        }, _getTarget: function () {
            return "string" == typeof this._.options.target ? DOM.getById(this._.options.target) : this._.options.target
        }
    });
    _context.register(Message, "Message")
}, {
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays",
    CSSTransitions: "Utils.CSSTransitions"
}), _context.invoke("Nittro.Flashes", function () {
    var DefaultPositioner = _context.extend(function (margin, defaultOrder) {
        this._ = {
            margin: "number" == typeof margin ? margin : 20,
            defaultOrder: defaultOrder || "above,rightOf,below,leftOf"
        }, "string" == typeof this._.defaultOrder && (this._.defaultOrder = this._.defaultOrder.split(/\s*,\s*/g))
    }, {
        supports: function (placement) {
            return "above" === placement || "below" === placement || "leftOf" === placement || "rightOf" === placement
        }, getDefaultOrder: function () {
            return this._.defaultOrder
        }, above: function (target, elem, force) {
            var res = {left: target.left + (target.width - elem.width) / 2, top: target.top - elem.height};
            if (force || res.left > this._.margin && res.left + elem.width < window.innerWidth - this._.margin && res.top > this._.margin && res.top + elem.height < window.innerHeight - this._.margin) return res
        }, below: function (target, elem, force) {
            var res = {left: target.left + (target.width - elem.width) / 2, top: target.bottom};
            if (force || res.left > this._.margin && res.left + elem.width < window.innerWidth - this._.margin && res.top + elem.height < window.innerHeight - this._.margin && res.top > this._.margin) return res
        }, leftOf: function (target, elem, force) {
            var res = {left: target.left - elem.width, top: target.top + (target.height - elem.height) / 2};
            if (force || res.top > this._.margin && res.top + elem.height < window.innerHeight - this._.margin && res.left > this._.margin && res.left + elem.width < window.innerWidth - this._.margin) return res
        }, rightOf: function (target, elem, force) {
            var res = {left: target.right, top: target.top + (target.height - elem.height) / 2};
            if (force || res.top > this._.margin && res.top + elem.height < window.innerHeight - this._.margin && res.left + elem.width < window.innerWidth - this._.margin && res.left > this._.margin) return res
        }
    });
    _context.register(DefaultPositioner, "DefaultPositioner")
}), _context.invoke("Nittro.Flashes", function (Message, DOM, Arrays) {
    var Service = _context.extend(function (positioner, options) {
        this._ = {
            positioner: positioner,
            options: Arrays.mergeTree({}, Service.defaults, options),
            globalHolder: DOM.create("div", {"class": "nittro-flash-global-holder"})
        }, "string" == typeof this._.options.layer ? this._.options.layer = DOM.getById(this._.options.layer) : this._.options.layer || (this._.options.layer = document.body), this._.options.layer.appendChild(this._.globalHolder), this._.options.classes || (this._.options.classes = DOM.getData(this._.options.layer, "flash-classes")), Message.defaults.classes = this._.options.classes, this._removeStatic()
    }, {
        STATIC: {defaults: {layer: null, classes: null}}, create: function (content, options) {
            return new Message(this, content, options)
        }, add: function (content, type, target, rich) {
            var options;
            return options = type && "object" == typeof type ? type : {
                type: type || "info",
                target: target,
                rich: rich
            }, this.create(content, options).show()
        }, getGlobalHolder: function () {
            return this._.globalHolder
        }, getLayer: function () {
            return this._.options.layer
        }, getPositioner: function () {
            return this._.positioner
        }, _removeStatic: function () {
            DOM.getByClassName("nittro-flash").forEach(function (elem) {
                DOM.getData(elem, "flash-dynamic") || Message.wrap(elem)
            }.bind(this))
        }
    });
    _context.register(Service, "Service")
}, {
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays",
    CSSTransitions: "Utils.CSSTransitions"
}), _context.invoke("Nittro.Routing", function (Strings, Arrays) {
    var URLRoute = _context.extend("Nittro.Object", function (mask) {
        URLRoute.Super.call(this), this._.mask = this._prepareMask(mask)
    }, {
        STATIC: {
            styles: {
                "int": parseInt, "float": parseFloat, bool: function (v) {
                    return !v.match(/^(?:0|false|)$/)
                }
            }
        }, match: function (url) {
            var params = this.tryMatch(url);
            params && (Arrays.mergeTree(params, url.getParams()), this.trigger("match", params))
        }, tryMatch: function (url) {
            var match = this._.mask.pattern.exec(url.getPath().replace(/^\/|\/$/g, ""));
            if (!match) return null;
            var i, n, p, v, params = {};
            for (match.shift(), i = 0, n = this._.mask.map.length; i < n; i++) p = this._.mask.map[i], v = decodeURIComponent(match[i]), p.style ? params[p.name] = URLRoute.styles[p.style].call(null, v) : params[p.name] = v;
            return params
        }, _prepareMask: function (mask) {
            var match, param, reTop = /^([<\[\]\(])|^([^<\[\]\(]+)/,
                reParam = /^([^ #>]+)(?: +([^ #>]+))?(?: +#([^ >]+))? *>/, reParen = /\((?!\?:)/g, reOptional = /^\?:/,
                map = [], pattern = ["^"];
            for (mask = mask.replace(/^\/|\/$/g, ""); mask.length;) {
                if (match = reTop.exec(mask), !match) throw new Error("Invalid mask, error near " + mask.substr(0, 10));
                if (mask = mask.substr(match[0].length), "<" === match[1]) {
                    if (param = reParam.exec(mask), !param) throw new Error("Invalid mask, error near " + mask.substr(0, 10));
                    if (mask = mask.substr(param[0].length), param[2] ? param[2] = param[2].replace(reParen, "(?:") : param[2] = "[^/]+", pattern.push("(", param[2], ")"), param[3] && !(param[3] in URLRoute.styles)) throw new Error("Unknown parameter style: " + param[3]);
                    map.push({name: param[1], style: param[3] || null})
                } else "[" === match[1] ? pattern.push("(?:") : "]" === match[1] ? pattern.push(")?") : "(" === match[1] ? pattern.push(reOptional.test(mask) ? "(" : "(?:") : pattern.push(Strings.escapeRegex(match[2]))
            }
            return pattern.push("$"), {pattern: new RegExp(pattern.join("")), map: map}
        }
    });
    _context.register(URLRoute, "URLRoute")
}, {Strings: "Utils.Strings", Arrays: "Utils.Arrays"}), _context.invoke("Nittro.Routing", function (DOM) {
    var DOMRoute = _context.extend("Nittro.Object", function (selector) {
        DOMRoute.Super.call(this), this._.selector = selector
    }, {
        match: function () {
            var matches = DOM.find(this._.selector);
            matches.length && this.trigger("match", matches)
        }
    });
    _context.register(DOMRoute, "DOMRoute")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Routing", function (DOMRoute, URLRoute, Url) {
    var Router = _context.extend("Nittro.Object", function (basePath) {
        Router.Super.call(this), this._.basePath = "/" + basePath.replace(/^\/|\/$/g, ""), this._.routes = {
            dom: {},
            url: {}
        }
    }, {
        getDOMRoute: function (selector) {
            return selector in this._.routes.dom || (this._.routes.dom[selector] = new DOMRoute(selector)), this._.routes.dom[selector]
        }, getURLRoute: function (mask) {
            return mask in this._.routes.url || (this._.routes.url[mask] = new URLRoute(mask)), this._.routes.url[mask]
        }, matchDOM: function () {
            for (var k in this._.routes.dom) this._.routes.dom.hasOwnProperty(k) && this._.routes.dom[k].match();
            return this
        }, matchURL: function () {
            var k, url = Url.fromCurrent();
            if (url.getPath().substr(0, this._.basePath.length) === this._.basePath) {
                url.setPath(url.getPath().substr(this._.basePath.length));
                for (k in this._.routes.url) this._.routes.url.hasOwnProperty(k) && this._.routes.url[k].match(url)
            }
            return this
        }, matchAll: function () {
            return this.matchURL(), this.matchDOM(), this
        }
    });
    _context.register(Router, "Router")
}, {Url: "Utils.Url"}), _context.invoke("Nittro.Extras.CheckList", function (DOM, Arrays) {
    var CheckList = _context.extend("Nittro.Object", function (options) {
        this._ = {
            options: Arrays.mergeTree({}, CheckList.defaults, options),
            scrolling: !1
        }, "string" == typeof this._.options.container && (this._.options.container = DOM.getById(this._.options.container)), "string" == typeof this._.options.scroll ? this._.options.scroll = {container: this._.options.scroll} : this._.options.scroll === !0 && (this._.options.scroll = {container: null}), this._.options.scroll && (this._.options.scroll.speed || (this._.options.scroll.speed = 3), this._.options.scroll.zoneSize || (this._.options.scroll.zoneSize = .1)), this._handleMouseDown = this._handleMouseDown.bind(this), this._handleClick = this._handleClick.bind(this), DOM.addListener(this._.options.container, "mousedown", this._handleMouseDown), DOM.addListener(this._.options.container, "click", this._handleClick)
    }, {
        STATIC: {
            defaults: {
                container: null,
                items: null,
                target: null,
                boundary: "parent",
                horizontal: !1,
                scroll: !0
            }
        }, destroy: function () {
            DOM.removeListener(this._.options.container, "mousedown", this._handleMouseDown), DOM.removeListener(this._.options.container, "click", this._handleClick)
        }, _handleClick: function (evt) {
            var target = this._getTarget(evt.target), items = this._getItems();
            items.indexOf(target) === -1 || 0 === evt.screenX && 0 === evt.screenY || evt.preventDefault()
        }, _handleMouseDown: function (mdevt) {
            var target = this._getTarget(mdevt.target), items = this._getItems(),
                start = target ? items.indexOf(target) : -1;
            if (start !== -1) {
                mdevt.preventDefault(), this.trigger("start", {target: target});
                var states, originalStates = items.map(this._getItemState.bind(this)), state = !originalStates[start],
                    pos = this._.options.horizontal ? mdevt.clientX : mdevt.clientY;
                this._setItemState(target, state), states = originalStates.slice(), states[start] = state;
                var handleMove = this._getMoveHandler(items, originalStates, states, start, state, pos),
                    end = function (muevt) {
                        var endTgt;
                        muevt && (muevt.preventDefault(), endTgt = this._getTarget(muevt.target)), DOM.removeListener(document, "mousemove", handleMove), DOM.removeListener(document, "mouseup", end), this._.scrolling = !1, endTgt === target || states.some(function (s, i) {
                            return i !== start && s !== originalStates[i]
                        }) ? this.trigger("change") : this._setItemState(target, !state), this.trigger("end"), endTgt && "function" == typeof endTgt.focus && endTgt.focus()
                    }.bind(this);
                DOM.addListener(document, "mousemove", handleMove), DOM.addListener(document, "mouseup", end)
            }
        }, _getMoveHandler: function (items, originalStates, states, start, state, prev) {
            var pos, offs, coffs, scroll = this._getScrollInfo(), boundaryElems = this._getBoundaryElements(items),
                boundaries = this._getBoundaries(boundaryElems, start, scroll.window.offset),
                horiz = this._.options.horizontal, n = items.length;
            scroll.container && scroll.container.offset > 0 && (boundaries = boundaries.map(function (b) {
                return b + scroll.container.offset
            }));
            var check = function (offs) {
                coffs = scroll.container ? scroll.container.offset : 0;
                for (var i = 0; i < n; i++) i !== start && originalStates[i] !== state && (i < start && offs < boundaries[i] - coffs || i > start && offs > boundaries[i] - coffs ? states[i] !== state && (this._setItemState(items[i], state), states[i] = state) : states[i] !== !state && (this._setItemState(items[i], !state), states[i] = !state))
            }.bind(this);
            return function (evt) {
                evt.preventDefault(), pos = horiz ? evt.clientX : evt.clientY, offs = scroll.window.offset + pos, check(offs), this._.scrolling ? (this._.scrolling.direction === -1 ? pos > prev : pos < prev) ? this._.scrolling = !1 : this._.scrolling.lastMousePosition = pos : pos < prev && (pos < scroll.window.prevThreshold || scroll.container && offs < scroll.container.prevThreshold) ? this._startScrolling(scroll, -1, pos, check) : pos > prev && (pos > scroll.window.nextThreshold || scroll.container && offs > scroll.container.nextThreshold) && this._startScrolling(scroll, 1, pos, check), prev = pos
            }.bind(this)
        }, _getTarget: function (target) {
            return this._.options.target ? this._.options.target.call(null, target) : target.control || target
        }, _setItemState: function (item, state) {
            item.checked = state
        }, _getItemState: function (item) {
            return item.checked
        }, _getItems: function () {
            return null === this._.options.items ? Arrays.createFrom(this._.options.container.getElementsByTagName("input")).filter(function (elem) {
                return "checkbox" === elem.type
            }) : DOM.getByClassName(this._.options.items, this._.options.container)
        }, _getBoundaryElements: function (items) {
            if (this._.options.boundary) {
                if ("parent" === this._.options.boundary) return items.map(function (elem) {
                    return elem.parentNode
                });
                var sel = this._.options.boundary.split(/\./);
                return DOM.closest(items, sel[0], sel[1])
            }
            return items
        }, _getBoundaries: function (elements, start, offset) {
            return this._.options.horizontal ? elements.map(function (elem, i) {
                var rect = elem.getBoundingClientRect();
                return i < start ? offset + rect.right : i > start ? offset + rect.left : null
            }) : elements.map(function (elem, i) {
                var rect = elem.getBoundingClientRect();
                return i < start ? offset + rect.bottom : i > start ? offset + rect.top : null
            })
        }, _startScrolling: function (info, dir, pos, check) {
            function canScroll(target, dir) {
                return dir < 0 ? target.offset > 0 : target.offset < target.max
            }

            if (this._.scrolling = {
                    direction: dir,
                    lastMousePosition: pos
                }, canScroll(info.window, dir) || info.container && canScroll(info.container, dir)) {
                var doScroll = function () {
                    if (info.container && canScroll(info.container, dir)) info.container.scrollBy(dir * this._.options.scroll.speed); else {
                        if (!canScroll(info.window, dir)) return;
                        info.window.scrollBy(dir * this._.options.scroll.speed)
                    }
                    this._.scrolling && (check(this._.scrolling.lastMousePosition + info.window.offset), window.requestAnimationFrame(doScroll))
                }.bind(this);
                window.requestAnimationFrame(doScroll)
            }
        }, _getScrollInfo: function () {
            var size, zone, container = this._getScrollContainerInfo(), body = document.body || {},
                html = document.documentElement || {}, win = {};
            return this._.options.horizontal ? (size = Math.max(body.scrollWidth, body.offsetWidth, html.clientWidth, html.scrollWidth, html.offsetWidth), zone = this._getScrollZoneSize(window.innerWidth), win.max = Math.max(0, size - window.innerWidth), win.offset = window.pageXOffset, win.offsetCross = window.pageYOffset, win.prevThreshold = zone, win.nextThreshold = window.innerWidth - zone, win.scrollBy = function (v) {
                win.offset = Math.max(0, Math.min(win.max, win.offset + v)), window.scrollTo(win.offset, win.offsetCross)
            }) : (size = Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight), zone = this._getScrollZoneSize(window.innerHeight), win.max = Math.max(0, size - window.innerHeight), win.offset = window.pageYOffset, win.offsetCross = window.pageXOffset, win.prevThreshold = zone, win.nextThreshold = window.innerHeight - zone, win.scrollBy = function (v) {
                win.offset = Math.max(0, Math.min(win.max, win.offset + v)), window.scrollTo(win.offsetCross, win.offset)
            }), container && (zone = this._getScrollZoneSize(container.size), container.prevThreshold = win.offset + container.position + zone, container.nextThreshold = win.offset + container.position + container.size - zone, container.scrollBy = function (v) {
                container.offset = Math.max(0, Math.min(container.max, container.offset + v)), container.element[container.prop] = container.offset
            }), {window: win, container: container}
        }, _getScrollZoneSize: function (size) {
            return this._.options.scroll.zoneSize < 1 ? this._.options.scroll.zoneSize * size : this._.options.scroll.zoneSize
        }, _getScrollContainerInfo: function () {
            function isScrollable(elem, overflow) {
                return elem[props.scrollSize] > elem[props.clientSize] && overflow in scrollable
            }

            function createInfo(elem) {
                var rect = elem.getBoundingClientRect();
                return {
                    element: elem,
                    offset: elem[props.scrollProp],
                    prop: props.scrollProp,
                    max: elem[props.scrollSize] - elem[props.clientSize],
                    size: elem[props.offsetSize],
                    position: rect[props.position]
                }
            }

            var elem, sel, overflow, props = this._.options.horizontal ? {
                scrollProp: "scrollLeft",
                scrollSize: "scrollWidth",
                clientSize: "clientWidth",
                offsetSize: "offsetWidth",
                overflow: "overflowX",
                position: "left"
            } : {
                scrollProp: "scrollTop",
                scrollSize: "scrollHeight",
                clientSize: "clientHeight",
                offsetSize: "offsetHeight",
                overflow: "overflowY",
                position: "top"
            }, scrollable = {scroll: 1, auto: 1};
            if ("string" == typeof this._.options.scroll.container) return sel = this._.options.scroll.container.split(/\./), elem = DOM.closest(this._.options.container, sel[0], sel[1]), overflow = DOM.getStyle(elem, "overflow", !1), isScrollable(elem, overflow) ? createInfo(elem) : null;
            if (this._.options.scroll.container) return elem = this._.options.scroll.container, overflow = DOM.getStyle(elem, "overflow", !1), isScrollable(elem, overflow) ? createInfo(elem) : null;
            elem = this._.options.container;
            do {
                if (overflow = DOM.getStyle(elem, "overflow", !1),
                        isScrollable(elem, overflow)) return createInfo(elem);
                elem = elem.parentNode
            } while (elem && elem !== document.body);
            return null
        }
    });
    _context.register(CheckList, "CheckList")
}, {DOM: "Utils.DOM", Arrays: "Utils.Arrays"}), _context.invoke("Nittro.Extras.Keymap", function () {
    var Keymap = _context.extend(function () {
        this._ = {map: {}}
    }, {
        STATIC: {MAC: /mac/i.test(navigator.platform), ACTION: "Control"}, add: function (key, handler) {
            return key = key.replace(/\baction\b/gi, Keymap.ACTION), this._.map[key] = handler, this
        }, remove: function (key) {
            delete this._.map[key]
        }, get: function (key) {
            return this._.map[key] || null
        }
    });
    Keymap.MAC && (Keymap.ACTION = "Meta"), _context.register(Keymap, "Keymap")
}, {}), _context.invoke("Nittro.Extras.Keymap", function (Arrays, DOM, undefined) {
    var anonId = 0, TabContext = _context.extend("Nittro.Object", function () {
        TabContext.Super.call(this), this._.items = [], this._.handlers = [], this._.lastFocused = null
    }, {
        add: function (items) {
            return Array.isArray(items) || (items = Arrays.createFrom(arguments)), this.insert(items)
        }, addFromContainer: function (container, links, index) {
            var elements = Arrays.createFrom(container.getElementsByTagName("input")).concat(Arrays.createFrom(container.getElementsByTagName("select"))).concat(Arrays.createFrom(container.getElementsByTagName("textarea"))).concat(Arrays.createFrom(container.getElementsByTagName("button")));
            links && (elements = elements.concat(Arrays.createFrom(container.getElementsByTagName("a"))));
            var radios = {};
            return elements = elements.filter(function (elem) {
                if ("INPUT" === elem.tagName) if ("radio" === elem.type) {
                    if (radios[elem.name]) return !1;
                    radios[elem.name] = !0
                } else if ("hidden" === elem.type) return !1;
                return elem.tabIndex !== -1
            }), elements.sort(function (a, b) {
                return a.tabIndex > 0 && b.tabIndex > 0 ? a.tabIndex - b.tabIndex || (4 & a.compareDocumentPosition(b) ? -1 : 1) : a.tabIndex > 0 ? -1 : b.tabIndex > 0 ? 1 : 4 & a.compareDocumentPosition(b) ? -1 : 1
            }), this.insert(elements, index)
        }, remove: function (items) {
            return Array.isArray(items) || (items = Arrays.createFrom(arguments)), items.forEach(function (item) {
                var index = this._.items.indexOf(item);
                index > -1 && (item instanceof Element ? DOM.removeListener(item, "focus", this._.handlers[index]) : "function" == typeof item.off && item.off("focus", this._.handlers[index]), this._.items.splice(index, 1), this._.handlers.splice(index, 1), this._.lastFocused >= index && this._.lastFocused--)
            }.bind(this)), this._.lastFocused && this._.lastFocused < 0 && (this._.lastFocused = null), this
        }, insert: function (items, index) {
            Array.isArray(items) || (items = [items]), "number" != typeof index && (index = this._.items.length);
            var handlers = [];
            return items = items.map(function (item) {
                if ("function" != typeof item.focus) throw new TypeError("Invalid item: doesn't have a focus() method");
                var handler = null, id = null;
                return item instanceof Element ? (item.hasAttribute("id") || item.setAttribute("id", "tabContext-item" + ++anonId), id = item.getAttribute("id"), handler = this._handleFocus.bind(this, id), DOM.addListener(item, "focus", handler)) : "function" == typeof item.on && (handler = this._handleFocus.bind(this, item), item.on("focus", handler)), handlers.push(handler), id || item
            }.bind(this)), items.unshift(index, 0), handlers.unshift(index, 0), this._.items.splice.apply(this._.items, items), this._.handlers.splice.apply(this._.handlers, handlers), this
        }, isDisabled: function () {
            return this._.items.some(function (item, index) {
                return this._getItem(item) && this._isDisabled(index)
            }.bind(this))
        }, focus: function () {
            if (this._.items.length) {
                for (this._.lastFocused = 0; this._isDisabled(this._.lastFocused);) if (this._.lastFocused++, this._.lastFocused >= this._.items.length) return void(this._.lastFocused = null);
                this._getItem(this._.items[this._.lastFocused]).focus()
            }
        }, next: function (cycle) {
            var count = this._.items.length;
            if (count) {
                var index = this._.lastFocused, start = index, cycled = !1;
                if (null === index) index = -1, start = 0; else if (this._.items[index] instanceof TabContext && this._.items[index].next(!1)) return !0;
                do if (index++, index >= count) {
                    if (cycle === !1) return !1;
                    index %= count, cycled = !0
                } else if (cycled && index >= start) return !1; while (this._isDisabled(index));
                return this._.lastFocused = index, this._getItem(this._.items[index]).focus(), !0
            }
        }, prev: function (cycle) {
            var count = this._.items.length;
            if (count) {
                var index = this._.lastFocused, start = index, cycled = !1;
                if (null === index) index = this._.items.length, start = index - 1; else if (this._.items[index] instanceof TabContext && this._.items[index].prev(!1)) return !0;
                do if (index--, index < 0) {
                    if (cycle === !1) return !1;
                    index += count, cycled = !0
                } else if (cycled && index <= start) return !1; while (this._isDisabled(index));
                return this._.lastFocused = index, this._getItem(this._.items[index]).focus(), !0
            }
        }, clear: function () {
            return this._.items.forEach(function (item, index) {
                "string" == typeof item ? DOM.removeListener(item, "focus", this._.handlers[index]) : "function" == typeof item.off && item.off("focus", this._.handlers[index]), item instanceof TabContext && item.destroy()
            }.bind(this)), this._.items = [], this._.handlers = [], this._.lastFocused = null, this
        }, destroy: function () {
            return this.clear()
        }, _getItem: function (item) {
            return "string" == typeof item ? DOM.getById(item) : item
        }, _isDisabled: function (index) {
            var item = this._getItem(this._.items[index]);
            return !item || (item instanceof Element ? item.disabled : "function" == typeof item.isDisabled && item.isDisabled())
        }, _handleFocus: function (item) {
            this._.lastFocused = this._.items.indexOf(item), this.trigger("focus")
        }
    });
    _context.register(TabContext, "TabContext")
}, {Arrays: "Utils.Arrays", DOM: "Utils.DOM"}), _context.invoke("Nittro.Extras.Keymap", function () {
    var i, Keys = {
        modifiers: {Shift: "shiftKey", Control: "ctrlKey", Alt: "altKey", Meta: "metaKey"},
        modifierOrder: ["Control", "Alt", "Shift", "Meta"],
        codes: {
            8: "Backspace",
            9: "Tab",
            13: "Enter",
            16: "Shift",
            17: "Control",
            18: "Alt",
            27: "Escape",
            32: "Space",
            33: "PageUp",
            34: "PageDown",
            35: "End",
            36: "Home",
            37: "ArrowLeft",
            38: "ArrowUp",
            39: "ArrowRight",
            40: "ArrowDown",
            45: "Insert",
            46: "Delete",
            91: "Meta",
            93: "Meta",
            186: ";",
            187: "=",
            188: ",",
            189: "-",
            190: ".",
            191: "/",
            192: "`",
            219: "[",
            220: "\\",
            221: "]",
            222: "'",
            224: "Meta",
            225: "AltGraph"
        }
    };
    for (i = 0; i < 10; i++) Keys.codes[i + 48] = i + "";
    for (i = 65; i < 91; i++) Keys.codes[i] = String.fromCharCode(i);
    _context.register(Keys, "Keys")
}), _context.invoke("Nittro.Extras.Keymap", function (DOM, Keymap, TabContext, Keys) {
    var Manager = _context.extend(function () {
        this._ = {
            keymaps: [],
            tabContexts: []
        }, DOM.addListener(document, "keydown", this._handleKeyDown.bind(this)), DOM.addListener(document, "keyup", this._handleKey.bind(this)), DOM.addListener(document, "keypress", this._handleKey.bind(this))
    }, {
        STATIC: {RE_TAB: /^(?:Shift\+)?Tab$/}, push: function (map) {
            for (var a = 0; a < arguments.length; a++) if (arguments[a]) if (arguments[a] instanceof Keymap) this._.keymaps.unshift(arguments[a]); else {
                if (!(arguments[a] instanceof TabContext)) throw new TypeError("Invalid argument, must be an instance of Keymap or TabContext");
                this._.tabContexts.unshift(arguments[a])
            } else ;
            return this
        }, pop: function (map) {
            var target, a, i;
            for (a = 0; a < arguments.length; a++) arguments[a] && (arguments[a] instanceof Keymap ? target = this._.keymaps : arguments[a] instanceof TabContext && (target = this._.tabContexts), (i = target.indexOf(arguments[a])) > -1 && target.splice(i, 1));
            return this
        }, _handleKeyDown: function (evt) {
            if (this._.keymaps.length || this._.tabContexts.length) {
                var handler, key = this._extractKey(evt);
                if (key) {
                    if (this._.keymaps.length && (handler = this._.keymaps[0].get(key))) return void(handler.call(null, key, evt) !== !1 && evt.preventDefault());
                    this._.tabContexts.length && Manager.RE_TAB.test(key) && (evt.preventDefault(), "Tab" === key ? this._.tabContexts[0].next() : this._.tabContexts[0].prev())
                }
            }
        }, _handleKey: function (evt) {
            if (this._.keymaps.length || this._.tabContexts.length) {
                var key = this._extractKey(evt);
                (this._.keymaps.length && this._.keymaps[0].get(key) || this._.tabContexts.length && Manager.RE_TAB.test(key)) && evt.preventDefault()
            }
        }, _extractKey: function (evt) {
            var key = Keys.codes[evt.which || evt.keyCode];
            if (key) {
                if (key in Keys.modifiers) return key;
                var modifiers = [];
                return Keys.modifierOrder.forEach(function (modifier) {
                    evt[Keys.modifiers[modifier]] && modifiers.push(modifier)
                }), modifiers.push(key), modifiers.join("+")
            }
        }
    });
    _context.register(Manager, "Manager")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Extras.Dialogs", function (DOM, CSSTransitions, Arrays, ReflectionClass) {
    var Dialog = _context.extend("Nittro.Object", function (options) {
        if (Dialog.Super.call(this), this._.options = Arrays.mergeTree({}, Dialog.getDefaults(this.constructor), options), this._.visible = !1, this._.scrollPosition = null, this._.keyMap = null, this._.tabContext = null, this._.elms = {
                holder: DOM.createFromHtml(this._.options.templates.holder),
                wrapper: DOM.createFromHtml(this._.options.templates.wrapper),
                content: null,
                buttons: null
            }, this._.elms.holder.appendChild(this._.elms.wrapper), this._.options.classes && DOM.addClass(this._.elms.holder, this._.options.classes), this._.options.content) this._.elms.content = this._.options.content, DOM.toggleClass(this._.elms.content, "nittro-dialog-content", !0), this._.options.content = null; else if (this._.options.text) {
            this._.elms.content = DOM.createFromHtml(this._.options.templates.content);
            var content = DOM.create("p");
            content.textContent = this._.options.text, this._.elms.content.appendChild(content)
        } else this._.options.html && (this._.elms.content = DOM.createFromHtml(this._.options.templates.content), DOM.html(this._.elms.content, this._.options.html));
        this._.elms.content && this._.elms.wrapper.appendChild(this._.elms.content), this._.options.buttons && (this._.options.buttons instanceof HTMLElement ? (this._.elms.buttons = this._.options.buttons, DOM.toggleClass(this._.elms.buttons, "nittro-dialog-buttons", !0), this._.options.buttons = null) : (this._.elms.buttons = DOM.createFromHtml(this._.options.templates.buttons), this._createButtons()), this._.elms.wrapper.appendChild(this._.elms.buttons));
        try {
            var keymap = ReflectionClass.from("Nittro.Extras.Keymap.Keymap"),
                tabContext = ReflectionClass.from("Nittro.Extras.Keymap.TabContext");
            this._.keyMap = keymap.newInstance(), this._.tabContext = tabContext.newInstance()
        } catch (e) {
        }
        if (this._.keyMap && this._.options.keyMap) {
            this._handleKey = this._handleKey.bind(this);
            for (var key in this._.options.keyMap) this._.options.keyMap.hasOwnProperty(key) && this._.keyMap.add(key, this._handleKey)
        }
        this._.tabContext && this._.elms.buttons && this._.tabContext.addFromContainer(this._.elms.buttons, !0), this.on("button:default", function () {
            this.hide()
        }), DOM.addListener(this._.elms.wrapper, "click", this._handleClick.bind(this)), DOM.addListener(this._.elms.holder, "touchmove", this._handleTouchScroll.bind(this)), this._handleScroll = this._handleScroll.bind(this)
    }, {
        STATIC: {
            defaults: {
                classes: null,
                content: null,
                html: null,
                text: null,
                buttons: null,
                keyMap: {Escape: "cancel"},
                templates: {
                    holder: '<div class="nittro-dialog-holder"></div>',
                    wrapper: '<div class="nittro-dialog-inner"></div>',
                    content: '<div class="nittro-dialog-content"></div>',
                    buttons: '<div class="nittro-dialog-buttons"></div>',
                    button: "<button></button>"
                }
            }, getDefaults: function (type) {
                var k, defaults = {};
                do {
                    if (type.defaults) for (k in type.defaults) type.defaults.hasOwnProperty(k) && !defaults.hasOwnProperty(k) && (defaults[k] = type.defaults[k]);
                    type = type.Super
                } while (type && type !== Dialog.Super);
                return defaults
            }, setDefaults: function (options) {
                Arrays.mergeTree(Dialog.defaults, options)
            }
        }, isVisible: function () {
            return this._.visible
        }, show: function () {
            if (!this._.visible) return this._.visible = !0, this._.scrollLock = {
                left: window.pageXOffset,
                top: window.pageYOffset
            }, /ipod|ipad|iphone/i.test(navigator.userAgent) && (this._.scrollPosition = window.pageYOffset, window.scrollTo(0, 0), this._.scrollLock.left = 0, this._.scrollLock.top = 0), DOM.addListener(window, "scroll", this._handleScroll), DOM.addClass(this._.elms.holder, "visible"), this.trigger("show"), CSSTransitions.run(this._.elms.holder).then(function () {
                return this.trigger("shown"), this
            }.bind(this))
        }, hide: function () {
            if (this._.visible) return this._.visible = !1, DOM.removeListener(window, "scroll", this._handleScroll), /ipod|ipad|iphone/i.test(navigator.userAgent) && (window.scrollTo(0, this._.scrollPosition), this._.scrollPosition = null), this.trigger("hide"), DOM.removeClass(this._.elms.holder, "visible"), CSSTransitions.run(this._.elms.holder).then(function () {
                return this.trigger("hidden"), this
            }.bind(this))
        }, getElement: function () {
            return this._.elms.holder
        }, getContent: function () {
            return this._.elms.content
        }, getButtons: function () {
            return this._.elms.buttons
        }, getKeyMap: function () {
            return this._.keyMap
        }, getTabContext: function () {
            return this._.tabContext
        }, destroy: function () {
            if (this._.visible) this.hide().then(this.destroy.bind(this)); else {
                this.trigger("destroy"), this._.elms.holder.parentNode && this._.elms.holder.parentNode.removeChild(this._.elms.holder), this.off();
                for (var k in this._.elms) this._.elms[k] = null
            }
        }, _createButtons: function () {
            var action, btn, def;
            for (action in this._.options.buttons) this._.options.buttons.hasOwnProperty(action) && (btn = DOM.createFromHtml(this._.options.templates.button), def = this._.options.buttons[action], "string" == typeof def && (def = {
                label: def,
                type: "button"
            }), DOM.setData(btn, "action", action), DOM.addClass(btn, "nittro-dialog-button", "text" === def.type ? "nittro-dialog-button-text" : ""), btn.textContent = def.label, this._.elms.buttons.appendChild(btn))
        }, _handleClick: function (evt) {
            var action = DOM.getData(evt.target, "action");
            action && (evt.preventDefault(), this.trigger("button", {action: action}))
        }, _handleKey: function (key, evt) {
            return !(evt.target && evt.target.tagName && evt.target.tagName.match(/^(input|button|textarea|select)$/i) && DOM.contains(this._.elms.wrapper, evt.target)) && void this.trigger("button", {action: this._.options.keyMap[key]})
        }, _handleTouchScroll: function (evt) {
            this._.elms.holder === evt.target && evt.preventDefault()
        }, _handleScroll: function () {
            window.scrollTo(this._.scrollLock.left, this._.scrollLock.top)
        }
    });
    _context.register(Dialog, "Dialog")
}, {
    DOM: "Utils.DOM",
    CSSTransitions: "Utils.CSSTransitions",
    Arrays: "Utils.Arrays",
    ReflectionClass: "Utils.ReflectionClass"
}), _context.invoke("Nittro.Extras.Dialogs", function (Dialog, DOM) {
    var Manager = _context.extend("Nittro.Object", function (baseZ) {
        Manager.Super.call(this), this._.stack = [], this._.zIndex = baseZ || 1e3, this._handleShow = this._handleShow.bind(this), this._handleHide = this._handleHide.bind(this)
    }, {
        createDialog: function (options) {
            var dlg = new Dialog(options);
            return this._setup(dlg), dlg
        }, hasOpenDialog: function () {
            return this._.stack.length > 0
        }, getTopmostOpenDialog: function () {
            return this._.stack.length ? this._.stack[0] : null
        }, getOpenDialogs: function () {
            return this._.stack.slice()
        }, _setup: function (dialog) {
            dialog.on("show", this._handleShow), dialog.on("hide", this._handleHide), this.trigger("setup", {dialog: dialog}), document.body.appendChild(dialog.getElement())
        }, _handleShow: function (evt) {
            this._.stack.length && DOM.toggleClass(this._.stack[0].getElement(), "topmost", !1), DOM.toggleClass(evt.target.getElement(), "topmost", !0), evt.target.getElement().style.zIndex = this._.zIndex + this._.stack.length, this._.stack.unshift(evt.target)
        }, _handleHide: function (evt) {
            DOM.toggleClass(evt.target.getElement(), "topmost", !1);
            var index = this._.stack.indexOf(evt.target);
            index > -1 && this._.stack.splice(index, 1), this._.stack.length && DOM.toggleClass(this._.stack[0].getElement(), "topmost", !0)
        }
    });
    _context.register(Manager, "Manager")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Extras.Confirm", function (Dialog, Arrays, ReflectionClass) {
    var Confirm = _context.extend(Dialog, function (options) {
        if (!(this instanceof Confirm)) {
            var dlg = ReflectionClass.from(Confirm).newInstanceArgs(arguments);
            return window.setTimeout(function () {
                dlg.show()
            }, 1), dlg
        }
        Confirm.Super.call(this, this._prepareOptions(arguments)), this._.promise = new Promise(function (fulfill) {
            this.on("button", function (evt) {
                this.destroy(), fulfill("confirm" === evt.data.action)
            })
        }.bind(this))
    }, {
        STATIC: {
            defaults: {
                classes: "nittro-dialog-confirm",
                buttons: {confirm: "OK", cancel: {label: "Cancel", type: "text"}},
                keyMap: {Enter: "confirm", Escape: "cancel"}
            }, setDefaults: function (defaults) {
                Arrays.mergeTree(Confirm.defaults, defaults)
            }
        }, _prepareOptions: function (args) {
            var options = args[0];
            return "string" == typeof options && (options = {text: options}, args.length > 1 && (options.buttons = {confirm: args[1]}, args.length > 2 ? "string" == typeof args[2] ? options.buttons.cancel = {
                label: args[2],
                type: "text"
            } : options.buttons.cancel = args[2] : options.buttons.cancel = {label: "Cancel", type: "text"})), options
        }, then: function (onfulfill, onreject) {
            return this._.promise.then(onfulfill, onreject)
        }
    });
    _context.register(Confirm, "Confirm")
}, {
    Dialog: "Nittro.Extras.Dialogs.Dialog",
    ReflectionClass: "Utils.ReflectionClass",
    Arrays: "Utils.Arrays"
}), _context.invoke("Nittro.Extras.Confirm", function (Manager, Confirm) {
    var ConfirmMixin = {
        createConfirm: function (options) {
            var dlg = Confirm.apply(null, arguments);
            return this._setup(dlg), dlg
        }
    };
    _context.mixin(Manager, ConfirmMixin)
}, {Manager: "Nittro.Extras.Dialogs.Manager"}), _context.invoke("Nittro.Extras.DropZone", function (Form, Vendor, DOM, Arrays, Strings) {
    var anonId = 0, DropZone = _context.extend("Nittro.Object", function (form, elem, options) {
        DropZone.Super.call(this), this._.form = form || null, this._.elems = [], this._.rules = null, this._.files = [], this._.dragElems = [], this._.options = Arrays.mergeTree({}, DropZone.defaults, options), this.validate = this.validate.bind(this), this.reset = this.reset.bind(this), this._serialize = this._serialize.bind(this), this._handleDragEvent = this._handleDragEvent.bind(this), this._handleDrop = this._handleDrop.bind(this), this._handleFieldChange = this._handleFieldChange.bind(this), this._.form && (this._.form.on("validate", this.validate), this._.form.on("serialize", this._serialize), this._.form.on("reset", this.reset), this.on("error:default", function (evt) {
            this._.form.trigger("error", {element: this._.field || this.getElement(), message: evt.data.message})
        }.bind(this))), this._.options.allowedTypes && (this._.options.allowedTypes = this._normalizeTypes(this._.options.allowedTypes)), this._.options.maxSize && (this._.options.maxSize = this._normalizeMaxSize(this._.options.maxSize)), this._.options.field && (this._.rules = DOM.getData(this._.options.field, "nette-rules"), this._.options.fieldName = this._.options.field.name, this._.options.required = this._.options.field.required, this._.options.multiple = this._.options.field.multiple, this._.options.field.accept ? this._.options.allowedTypes = this._normalizeTypes(this._.options.field.accept) : this._.options.allowedTypes && (this._.options.field.accept = this._formatAccept(this._.options.allowedTypes)), this._.options.field.required = !1, this._.options.field.removeAttribute("data-nette-rules"), this._.options.field.hasAttribute("id") || this._.options.field.setAttribute("id", "dropzone-field" + ++anonId), this._.options.field = this._.options.field.getAttribute("id"), DOM.addListener(document, "change", this._handleFieldChange)), elem && this.attach(elem)
    }, {
        STATIC: {
            create: function (formLocator, from) {
                if (!(from instanceof HTMLInputElement) || "file" !== from.type) throw new Error("Invalid argument, must be a file input");
                var form = from.form ? formLocator.getForm(from.form) : null;
                return new DropZone(form, null, {field: from})
            },
            TYPES: {},
            defaults: {
                field: null,
                fieldName: null,
                required: !1,
                allowedTypes: null,
                maxSize: null,
                multiple: !0,
                netteValidate: {perFile: !0, onSubmit: !0},
                messages: {
                    empty: "This field is required.",
                    invalidType: "File %s isn't an allowed kind of file",
                    exceededSize: "File %s is too large."
                }
            }
        }, attach: function (elem) {
            return this._.dragElems = [], this._.elems.push(elem), 1 === this._.elems.length && (DOM.addListener(document.body, "dragenter", this._handleDragEvent), DOM.addListener(document.body, "dragover", this._handleDragEvent), DOM.addListener(document.body, "dragleave", this._handleDragEvent), DOM.addListener(document.body, "drop", this._handleDrop)), this
        }, detach: function () {
            return this._.elems.length && (DOM.removeListener(document.body, "dragenter", this._handleDragEvent), DOM.removeListener(document.body, "dragover", this._handleDragEvent), DOM.removeListener(document.body, "dragleave", this._handleDragEvent), DOM.removeListener(document.body, "drop", this._handleDrop), this._.dragElems = [], this._.elems = []), this
        }, isAttached: function () {
            return this._.elems.length > 0
        }, getElement: function () {
            return this._.elems.length ? this._.elems[0] : null
        }, getElements: function () {
            return this._.elems
        }, setAllowedTypes: function (allowedTypes) {
            return this._.options.allowedTypes = allowedTypes ? this._normalizeTypes(allowedTypes) : null, this
        }, setMaxSize: function (size) {
            return this._.options.maxSize = size ? this._normalizeMaxSize(size) : null, this
        }, setRequired: function (required) {
            return this._.options.required = required !== !1, this
        }, setMultiple: function (multiple) {
            return this._.options.multiple = multiple !== !1, this
        }, setFieldName: function (fieldName) {
            return this._.options.fieldName = fieldName, this
        }, hasFiles: function () {
            return this._.files.length > 0
        }, getFiles: function () {
            return this._.files.slice()
        }, getFile: function (index) {
            return this._.files[index] || null
        }, isImage: function (file) {
            return /^image\/.+$/i.test(file.type)
        }, loadImages: function () {
            var queue = [];
            return this._.files.forEach(function (file) {
                file.type.match(/^image\/.+$/i) && queue.push(this.loadImage(file))
            }.bind(this)), Promise.all(queue)
        }, loadImage: function (file) {
            return new Promise(function (fulfill, reject) {
                var reader = new FileReader, image = new Image;
                reader.onload = function () {
                    image.src = reader.result, fulfill(image)
                }, reader.onerror = function () {
                    reject()
                }, reader.readAsDataURL(file)
            })
        }, addFiles: function (files) {
            var i = 0, n = this._.options.multiple ? files.length : 1;
            for (this._.options.multiple || (this._.files = []); i < n; i++) this.addFile(files instanceof FileList ? files.item(i) : files[i]);
            return this
        }, addFile: function (file) {
            try {
                this._validateFile(file);
                var evt = this.trigger("file", {file: file, index: this._.files.length});
                evt.isDefaultPrevented() || this._.files.push(file)
            } catch (e) {
                if (e instanceof ValidationError) this.trigger("error", {
                    message: e.message,
                    file: file
                }); else if (!(e instanceof NetteValidationError)) throw e
            }
            return this
        }, removeFile: function (file) {
            return "number" != typeof file && (file = this._.files.indexOf(file)), file >= 0 && file < this._.files.length && this._.files.splice(file, 1), this
        }, reset: function () {
            return this._.files = [], this
        }, destroy: function () {
            this.trigger("destroy"), this.detach(), this.off(), this._.files = [], this._.form && (this._.form.off("validate", this.validate), this._.form.off("serialize", this._serialize), this._.form.off("reset", this.reset)), this._.form = null, this._hasField() && DOM.removeListener(document, "change", this._handleFieldChange)
        }, validate: function (evt) {
            this._.options.netteValidate.perFile && this._hasField() && this._.rules && !Vendor.validateControl(this._getField(), this._.rules, !1, {value: this._.files}) ? evt.preventDefault() : this._.options.required && !this._.files.length && (evt.preventDefault(), this.trigger("error", {message: this._formatErrorMessage("empty")}))
        }, formatSize: function (bytes) {
            for (var units = ["kB", "MB", "GB", "TB"], unit = "B"; bytes > 1024 && units.length;) unit = units.shift(), bytes /= 1024;
            return ("B" === unit ? bytes : bytes.toFixed(2)) + " " + unit
        }, _isValidTarget: function (elem, withDescendants) {
            return this._.elems.length > 0 && (this._.elems.indexOf(elem) > -1 || withDescendants && this._.elems.some(function (el) {
                return DOM.contains(el, elem)
            }))
        }, _hasField: function () {
            return !!this._.options.field
        }, _getField: function () {
            return this._.options.field ? DOM.getById(this._.options.field) : null
        }, _validateFile: function (file) {
            if (this._.options.netteValidate.perFile && this._hasField() && this._.rules && !Vendor.validateControl(this._getField(), this._.rules, !1, {value: [file]})) throw new NetteValidationError;
            if (!this._validateType(file.name, file.type)) throw new ValidationError(this._formatErrorMessage("invalidType", [file.name, file.type]));
            if (!this._validateSize(file.size)) throw new ValidationError(this._formatErrorMessage("exceededSize", [file.name, this.formatSize(file.size), this.formatSize(this._.options.maxSize)]))
        }, _validateType: function (name, type) {
            return !this._.options.allowedTypes || this._.options.allowedTypes.some(function (pattern) {
                return "." === pattern.charAt(0) ? !name || name.match(new RegExp(Strings.escapeRegex(pattern) + "$", "i")) : !type || type.match(new RegExp("^" + Strings.escapeRegex(pattern).replace(/\/\\\*$/, "/.+") + "$", "i"))
            })
        }, _validateSize: function (size) {
            return !this._.options.maxSize || size <= this._.options.maxSize
        }, _handleFieldChange: function (evt) {
            if (this._hasField() && evt.target === this._getField() && evt.target.files.length) if (this.addFiles(evt.target.files), this._.form) this._.form.setValue(this._.options.fieldName, null); else {
                var html = evt.target.parentNode.innerHTML;
                DOM.html(evt.target.parentNode, html)
            }
        }, _handleDrop: function (evt) {
            if (this._.dragElems = [], !evt.defaultPrevented && this._isValidTarget(evt.target, !0)) {
                evt.preventDefault();
                var drop = this.trigger("drop", {files: evt.dataTransfer.files});
                drop.isDefaultPrevented() || this.addFiles(evt.dataTransfer.files)
            }
        }, _handleDragEvent: function (evt) {
            if (evt.preventDefault(), "dragenter" === evt.type) this._.dragElems.indexOf(evt.target) === -1 && this._.dragElems.push(evt.target), 1 === this._.dragElems.length && this.trigger("body-enter", {files: evt.dataTransfer.files}), this._isValidTarget(evt.target) && this.trigger("zone-enter", {files: evt.dataTransfer.files}); else if ("dragleave" === evt.type) {
                var index = this._.dragElems.indexOf(evt.target);
                index > -1 && this._.dragElems.splice(index, 1), this._isValidTarget(evt.target) && this.trigger("zone-leave"), this._.dragElems.length || this.trigger("body-leave")
            }
        }, _serialize: function (evt) {
            for (var i = 0; i < this._.files.length; i++) evt.data.append(this._.options.fieldName, this._.files[i])
        }, _formatAccept: function (allowedTypes) {
            return allowedTypes.join(",")
        }, _normalizeTypes: function (allowedTypes) {
            return "string" == typeof allowedTypes ? allowedTypes.trim().split(/\s*,\s*/g) : allowedTypes
        }, _normalizeMaxSize: function (size) {
            if ("string" == typeof size) {
                var unit;
                switch ((unit = size.match(/(k|M|G|T)?B$/)) ? (unit = unit[1], size = size.replace(/(k|M|G|T)?B$/, "")) : unit = "B", size = parseFloat(size.trim()), unit) {
                    case"T":
                        size *= 1024;
                    case"G":
                        size *= 1024;
                    case"M":
                        size *= 1024;
                    case"k":
                        size *= 1024
                }
            }
            return size
        }, _formatErrorMessage: function (type, params) {
            var message = this._.options.messages[type];
            return params && (message = Strings.vsprintf(message, params)), message
        }
    }), ValidationError = _context.extend(function (message) {
        this.message = message
    }), NetteValidationError = _context.extend(function () {
    });
    _context.register(DropZone, "DropZone")
}, {
    Form: "Nittro.Forms.Form",
    Vendor: "Nittro.Forms.Vendor",
    Arrays: "Utils.Arrays",
    Strings: "Utils.Strings",
    DOM: "Utils.DOM"
}), _context.invoke("Nittro.Extras.Paginator", function (Arrays, Strings, DOM, undefined) {
    var Paginator = _context.extend("Nittro.Object", function (ajax, history, options) {
        if (Paginator.Super.call(this), this._.ajax = ajax, this._.history = history, this._.options = Arrays.mergeTree({}, Paginator.defaults, options), this._.container = this._.options.container, this._.viewport = this._resolveViewport(this._.options.container, this._.options.viewport), null === this._.options.pageSize) throw new Error("You must specify the page size (number of items per page)");
        if (null === this._.options.pageCount) throw new Error("You must specify the page count");
        null === this._.options.margin && (this._.options.margin = this._computeMargin()), null === this._.options.history && (this._.options.history = !!this._.options.url), "string" == typeof this._.options.itemRenderer ? this._.template = DOM.getById(this._.options.itemRenderer).innerHTML : "string" == typeof this._.options.template && (this._.template = this._.options.template), null === this._.options.responseProcessor && (this._.options.responseProcessor = this._processResponse.bind(this)), this._.firstPage = this._.lastPage = this._.currentPage = this._.options.currentPage, this._.lock = !1, this._.previousItems = null, this._.previousLock = {
            time: Date.now() + 1e3,
            threshold: this._computeElemOffset(this._.container.firstElementChild)
        }, this._.previousThreshold = this._computePreviousThreshold(), this._.nextThreshold = this._computeNextThreshold(), this._.handleScroll = this._handleScroll.bind(this);
        var prevElem = this._.container.tagName.toLowerCase();
        this._.prevContainer = DOM.create(prevElem, {"class": "nittro-paginator-previous"}), this._.container.insertBefore(this._.prevContainer, this._.container.firstChild), this._.pageThresholds = [{
            page: this._.currentPage,
            threshold: this._computeElemOffset(this._.prevContainer.nextElementSibling) + this._getScrollTop()
        }], Array.isArray(this._.options.items) && this._getItems(this._.options.currentPage), this._preparePreviousPage(), DOM.addListener(this._.viewport, "scroll", this._.handleScroll)
    }, {
        STATIC: {
            defaults: {
                container: null,
                viewport: null,
                itemRenderer: null,
                template: null,
                items: null,
                url: null,
                responseProcessor: null,
                history: null,
                margin: null,
                currentPage: 1,
                pageCount: null,
                pageSize: null
            }
        }, destroy: function () {
            DOM.removeListener(this._.viewport, "scroll", this._.handleScroll), this._.container = this._.viewport = this._.options = null
        }, _handleScroll: function () {
            this._.lock || (this._.lock = !0, window.requestAnimationFrame(function () {
                this._.lock = !1;
                var i, t, p, n, top = this._getScrollTop();
                if (null !== this._.nextThreshold && top > this._.nextThreshold ? (this._.nextThreshold = null, this._renderNextPage()) : this._.previousLock ? this._.previousLock.time < Date.now() && top > this._.previousLock.threshold && (this._.previousLock = null) : null !== this._.previousThreshold && top < this._.previousThreshold && (this._.previousThreshold = null, this._renderPreviousPage()), (!this._.previousLock || this._.previousLock.time < Date.now()) && this._.options.history) for (i = 1, t = this._.pageThresholds.length; i <= t; i++) if (p = this._.pageThresholds[i - 1], n = this._.pageThresholds[i], top > p.threshold && (!n || top < n.threshold) && p.page !== this._.currentPage) {
                    this._.currentPage = p.page, this._.history.replace(this._getPageUrl(p.page, !0));
                    break
                }
            }.bind(this)))
        }, _getPageUrl: function (page, history) {
            var url = history && "boolean" != typeof this._.options.history ? this._.options.history : this._.options.url;
            return "function" == typeof url ? url.call(null, page) : url.replace(/%page%/g, page)
        }, _getItems: function (page) {
            if (Array.isArray(this._.options.items)) {
                var items, args = new Array(this._.options.pageSize);
                return args.unshift((page - 1) * this._.options.pageSize, this._.options.pageSize), items = this._.options.items.splice.apply(this._.options.items, args), Promise.resolve(items)
            }
            var url = this._getPageUrl(page);
            return this._.ajax.get(url).then(this._.options.responseProcessor).then(function (items) {
                return Array.isArray(items) ? items : []
            })
        }, _processResponse: function (response) {
            return response.getPayload().items || []
        }, _preparePreviousPage: function () {
            this._.firstPage > 1 ? this._.previousItems = this._getItems(this._.firstPage - 1).then(function (items) {
                return items = items.map(this._createItem.bind(this)).map(function (elem) {
                    return this._.prevContainer.appendChild(elem), elem
                }.bind(this)), this.trigger("page-prepared", {items: items}), items
            }.bind(this)) : this._.previousItems = Promise.resolve(null)
        }, _renderPreviousPage: function () {
            return this._.previousItems.then(function (items) {
                if (items) {
                    this._.firstPage--;
                    var delta, i, scrollTop = this._getScrollTop(),
                        style = window.getComputedStyle(this._.prevContainer), first = items[0],
                        existing = this._.prevContainer.nextElementSibling || null,
                        itemStyle = window.getComputedStyle(first),
                        pt = parseFloat(style.paddingTop.replace(/px$/, "")),
                        pb = parseFloat(style.paddingBottom.replace(/px$/, "")), m = 0;
                    for (style.display.match(/flex$/) || "none" !== itemStyle["float"] || (m = Math.max(parseFloat(itemStyle.marginTop.replace(/px$/, "")), parseFloat(itemStyle.marginBottom.replace(/px$/, "")))), delta = this._.prevContainer.clientHeight - pt - pb - m, scrollTop += delta, i = 0; i < items.length; i++) this._.container.insertBefore(items[i], existing);
                    this.trigger("page-rendered", {items: items}), window.requestAnimationFrame(function () {
                        this._setScrollTop(scrollTop), this._.pageThresholds.forEach(function (t) {
                            t.threshold += delta
                        }), this._.pageThresholds.unshift({
                            page: this._.firstPage,
                            threshold: this._computeElemOffset(first) + scrollTop
                        })
                    }.bind(this)), this._preparePreviousPage(), this._.previousThreshold = this._computePreviousThreshold()
                }
            }.bind(this))
        }, _renderNextPage: function () {
            return this._getItems(this._.lastPage + 1).then(function (items) {
                this._.lastPage++, items = items.map(this._createItem.bind(this));
                for (var i = 0; i < items.length; i++) this._.container.appendChild(items[i]);
                this.trigger("page-rendered", {items: items}), this._.nextThreshold = this._computeNextThreshold(), this._.pageThresholds.push({
                    page: this._.lastPage,
                    threshold: this._computeElemOffset(items[0]) + this._getScrollTop()
                })
            }.bind(this))
        }, _createItem: function (data) {
            var item = this._renderItem(data);
            if ("string" == typeof item && (item = DOM.createFromHtml(item)), Array.isArray(item)) throw new Error("Rendered item contains more than one root HTML element");
            return item
        }, _renderItem: function (data) {
            return "string" == typeof data ? data : this._.template ? this._.template.replace(/%([a-z0-9_.-]+)%/gi, function () {
                var i, p, path = arguments[1].split(/\./g), cursor = data, n = path.length;
                for (i = 0; i < n; i++) {
                    if (p = path[i], Array.isArray(cursor) && p.match(/^\d+$/) && (p = parseInt(p)), cursor[p] === undefined) return "";
                    cursor = cursor[p]
                }
                return Strings.escapeHtml(cursor + "")
            }) : this._.options.itemRenderer.call(null, data)
        }, _computePreviousThreshold: function () {
            return this._.firstPage > 1 ? this._.options.margin : null
        }, _computeNextThreshold: function () {
            if (!this._.container.lastElementChild || this._.lastPage >= this._.options.pageCount) return null;
            var ofs = this._computeElemOffset(this._.container.lastElementChild, "bottom");
            return Math.max(0, ofs + this._getScrollTop() - this._getViewportHeight() - this._.options.margin)
        }, _computeElemOffset: function (elem, edge) {
            var offset = elem.getBoundingClientRect()[edge || "top"];
            return this._.viewport !== window && (offset -= this._.viewport.getBoundingClientRect().top), offset
        }, _computeMargin: function () {
            return this._getViewportHeight() / 2
        }, _getViewportHeight: function () {
            return this._.viewport.clientHeight || this._.viewport.innerHeight
        }, _getScrollTop: function () {
            return this._.viewport === window ? window.pageYOffset : this._.viewport.scrollTop
        }, _setScrollTop: function (to) {
            this._.viewport === window ? window.scrollTo(0, to) : this._.viewport.scrollTop = to
        }, _resolveViewport: function (elem, viewport) {
            function isScrollable(elem) {
                var style = window.getComputedStyle(elem);
                return "auto" === style.overflow || "scroll" === style.overflow || "auto" === style.overflowY || "scroll" === style.overflowY
            }

            if ("auto" === viewport) for (viewport = elem; viewport && viewport !== document.body && !isScrollable(viewport);) viewport = viewport.parentNode; else if (null === viewport) return window;
            return viewport && viewport !== document.body ? viewport : window
        }
    });
    _context.register(Paginator, "Paginator")
}, {
    Arrays: "Utils.Arrays",
    Strings: "Utils.Strings",
    DOM: "Utils.DOM"
}), _context.invoke("Nittro.Extras.Storage", function () {
    var Storage = _context.extend(function (namespace, persistent) {
        this._.persistent = persistent, this._.engine = persistent ? window.localStorage : window.sessionStorage, this._.items = {}, this._.namespace = namespace || "", this._.filters = {
            "in": [],
            out: []
        }
    }, {
        STATIC: {NAMESPACE_SEPARATOR: "/", FILTER_IN: "in", FILTER_OUT: "out"}, getItem: function (key, need) {
            var value = this._.engine.getItem(this._formatKey(key));
            if (null === value) {
                if (need) throw new Error;
                return null
            }
            return this._applyFilters(this._parseValue(value), Storage.FILTER_OUT)
        }, setItem: function (key, value) {
            return value = this._stringifyValue(this._applyFilters(value, Storage.FILTER_IN)), this._.engine.setItem(this._formatKey(key), value), this
        }, hasItem: function (key) {
            return null !== this._.engine.getItem(this._formatKey(key))
        }, removeItem: function (key) {
            return this._.engine.removeItem(this._formatKey(key)), this
        }, clear: function () {
            for (var ns = this._.namespace + Storage.NAMESPACE_SEPARATOR, nsl = ns.length, rem = [], i = 0; i < this._.engine.length; i++) {
                var k = this._.engine.key(i);
                k.substr(0, nsl) === ns && rem.push(k)
            }
            for (; rem.length;) this._.engine.removeItem(rem.shift());
            return this
        }, walk: function (callback) {
            for (var ns = this._.namespace + Storage.NAMESPACE_SEPARATOR, nsl = ns.length, i = 0; i < this._.engine.length; i++) {
                var k = this._.engine.key(i);
                if (k.substr(0, nsl) === ns) {
                    k = k.substr(nsl);
                    var v = this.getItem(k);
                    callback.call(v, k, v)
                }
            }
        }, getNamespace: function (namespace) {
            return new this.constructor((this._.namespace ? this._.namespace + Storage.NAMESPACE_SEPARATOR : "") + namespace, this._.persistent)
        }, addFilter: function (callback, type) {
            return this._.filters[type].push(callback), this
        }, _formatKey: function (key) {
            return this._.namespace + Storage.NAMESPACE_SEPARATOR + key
        }, _parseValue: function (value) {
            return JSON.parse(value)
        }, _stringifyValue: function (value) {
            return JSON.stringify(value)
        }, _applyFilters: function (value, type) {
            for (var i = 0; i < this._.filters[type].length; i++) value = this._.filters[type][i](value);
            return value
        }
    });
    _context.register(Storage, "Storage")
}), _context.invoke("Nittro.Ajax.Bridges.AjaxDI", function (Nittro) {
    var AjaxExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        AjaxExtension.Super.call(this, containerBuilder, config)
    }, {
        STATIC: {defaults: {allowOrigins: null}}, load: function () {
            var builder = this._getContainerBuilder(), config = this._getConfig(AjaxExtension.defaults);
            builder.addServiceDefinition("ajax", {
                factory: "Nittro.Ajax.Service()",
                args: {options: config},
                run: !0,
                setup: ["::setTransport(Nittro.Ajax.Transport.Native())"]
            })
        }
    });
    _context.register(AjaxExtension, "AjaxExtension")
}), _context.invoke("Nittro.Page.Bridges.PageDI", function (Nittro) {
    var PageExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        PageExtension.Super.call(this, containerBuilder, config)
    }, {
        STATIC: {
            defaults: {
                whitelistHistory: !1,
                whitelistLinks: !1,
                whitelistRedirects: !1,
                backgroundErrors: !1,
                csp: null,
                transitions: {defaultSelector: ".nittro-transition-auto"},
                i18n: {
                    connectionError: "There was an error connecting to the server. Please check your internet connection and try again.",
                    unknownError: "There was an error processing your request. Please try again later."
                }
            }
        }, load: function () {
            var builder = this._getContainerBuilder(), config = this._getConfig(PageExtension.defaults);
            if (builder.addServiceDefinition("page", {
                    factory: "Nittro.Page.Service()",
                    args: {options: {whitelistLinks: config.whitelistLinks, backgroundErrors: config.backgroundErrors}},
                    run: !0
                }), builder.addServiceDefinition("ajaxAgent", {
                    factory: "Nittro.Page.AjaxAgent()",
                    args: {options: {whitelistRedirects: config.whitelistRedirects}},
                    run: !0
                }), builder.addServiceDefinition("historyAgent", {
                    factory: "Nittro.Page.HistoryAgent()",
                    args: {options: {whitelistHistory: config.whitelistHistory}},
                    run: !0
                }), builder.addServiceDefinition("snippetAgent", "Nittro.Page.SnippetAgent()!"), builder.addServiceDefinition("snippetManager", "Nittro.Page.SnippetManager()"), builder.addServiceDefinition("history", "Nittro.Page.History()"), "function" == typeof window.ga && builder.addServiceDefinition("googleAnalyticsHelper", "Nittro.Page.GoogleAnalyticsHelper()!"), config.transitions && builder.addServiceDefinition("transitionAgent", {
                    factory: "Nittro.Page.TransitionAgent()",
                    args: {options: {defaultSelector: config.transitions.defaultSelector}},
                    run: !0
                }), config.csp !== !1) {
                var nonce = document.getElementsByTagName("script").item(0).getAttribute("nonce") || null;
                (config.csp || nonce) && builder.addServiceDefinition("cspAgent", {
                    factory: "Nittro.Page.CspAgent()",
                    args: {nonce: nonce},
                    run: !0
                })
            }
        }, setup: function () {
            var builder = this._getContainerBuilder(), config = this._getConfig();
            builder.hasServiceDefinition("flashes") && (builder.addServiceDefinition("flashAgent", "Nittro.Page.Bridges.PageFlashes.FlashAgent()!"), builder.getServiceDefinition("page").addSetup(function (flashes) {
                this.on("error:default", function (evt) {
                    "connection" === evt.data.type ? flashes.add(config.i18n.connectionError, "error") : "abort" !== evt.data.type && flashes.add(config.i18n.unknownError, "error")
                })
            }))
        }
    });
    _context.register(PageExtension, "PageExtension")
}), _context.invoke("Nittro.Page.Bridges.PageFlashes", function () {
    var FlashAgent = _context.extend(function (page, flashes) {
        this._ = {
            page: page,
            flashes: flashes
        }, this._handleResponse = this._handleResponse.bind(this), this._.page.on("transaction-created", this._initTransaction.bind(this))
    }, {
        _initTransaction: function (evt) {
            evt.data.transaction.on("ajax-response", this._handleResponse)
        }, _handleResponse: function (evt) {
            var payload = evt.data.response.getPayload();
            !payload.redirect && payload.flashes && this._showFlashes(payload.flashes)
        }, _showFlashes: function (flashes) {
            var id, i;
            for (id in flashes) if (flashes.hasOwnProperty(id) && flashes[id]) for (i = 0; i < flashes[id].length; i++) this._.flashes.add(flashes[id][i].message, flashes[id][i].type, id + "es")
        }
    });
    _context.register(FlashAgent, "FlashAgent")
}), _context.invoke("Nittro.Forms.Bridges.FormsDI", function (Nittro) {
    var FormsExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        FormsExtension.Super.call(this, containerBuilder, config)
    }, {
        STATIC: {defaults: {whitelistForms: !1, autoResetForms: !0}}, load: function () {
            var builder = this._getContainerBuilder();
            builder.addServiceDefinition("formLocator", "Nittro.Forms.Locator()")
        }, setup: function () {
            var builder = this._getContainerBuilder(), config = this._getConfig(FormsExtension.defaults);
            builder.hasServiceDefinition("page") && builder.getServiceDefinition("page").addSetup(function (formLocator) {
                this.initForms(formLocator, config)
            })
        }
    });
    _context.register(FormsExtension, "FormsExtension")
}), _context.invoke("Nittro.Forms.Bridges.FormsPage", function (Service, DOM) {
    var FormsMixin = {
        initForms: function (formLocator, options) {
            this._.formLocator = formLocator, this._.options.whitelistForms = options.whitelistForms, this._.options.autoResetForms = options.autoResetForms, DOM.addListener(document, "submit", this._handleSubmit.bind(this)), DOM.addListener(document, "click", this._handleButtonClick.bind(this)), this._.snippetManager.on("after-update", this._cleanupForms.bind(this)), this.on("transaction-created", this._initFormTransaction.bind(this))
        }, sendForm: function (form, evt) {
            var frm = this._.formLocator.getForm(form);
            return this.open(form.action, form.method, frm.serialize(), {
                event: evt,
                element: form
            }).then(this._handleFormSuccess.bind(this, frm))
        }, _initFormTransaction: function (evt) {
            if (evt.data.context.element && evt.data.context.element instanceof HTMLFormElement) {
                var data = {form: this._.formLocator.getForm(evt.data.context.element), allowReset: !0};
                evt.data.transaction.on("ajax-response", this._handleFormResponse.bind(this, data)), evt.data.transaction.then(this._handleFormSuccess.bind(this, data), function () {
                })
            }
        }, _handleFormResponse: function (data, evt) {
            var payload = evt.data.response.getPayload();
            "allowReset" in payload && (data.allowReset = payload.allowReset)
        }, _handleFormSuccess: function (data) {
            data.allowReset && data.form.getElement() && DOM.getData(data.form.getElement(), "reset", this._.options.autoResetForms) && data.form.reset()
        }, _handleSubmit: function (evt) {
            !evt.defaultPrevented && evt.target instanceof HTMLFormElement && this._checkForm(evt.target) && this.sendForm(evt.target, evt)
        }, _handleButtonClick: function (evt) {
            if (!(evt.defaultPrevented || evt.ctrlKey || evt.shiftKey || evt.altKey || evt.metaKey || evt.button > 0)) {
                var frm, btn = DOM.closest(evt.target, "button") || DOM.closest(evt.target, "input");
                btn && "submit" === btn.type && btn.form && this._checkForm(btn.form) && (frm = this._.formLocator.getForm(btn.form), frm.setSubmittedBy(btn.name || null))
            }
        }, _checkForm: function (form) {
            return !form.getAttribute("target") && DOM.getData(form, "ajax", !this._.options.whitelistForms)
        }, _cleanupForms: function () {
            this._.formLocator.refreshForms()
        }
    };
    _context.register(FormsMixin, "FormsMixin"), _context.mixin(Service, FormsMixin), Service.defaults.whitelistForms = !1, Service.defaults.autoResetForms = !0
}, {
    Service: "Nittro.Page.Service",
    DOM: "Utils.DOM"
}), _context.invoke("Nittro.Flashes.Bridges.FlashesDI", function (Neon, NeonEntity, HashMap) {
    var FlashesExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        FlashesExtension.Super.call(this, containerBuilder, config)
    }, {
        STATIC: {defaults: {layer: null, classes: null, positioner: {defaultOrder: null, margin: null}}},
        load: function () {
            var positioner, builder = this._getContainerBuilder(), config = this._getConfig(FlashesExtension.defaults);
            positioner = "string" == typeof config.positioner ? config.positioner.match(/^@[^(]+$/) ? config.positioner : Neon.decode("[" + config.positioner + "]").shift() : new NeonEntity("Nittro.Flashes.DefaultPositioner", HashMap.from(config.positioner)), builder.addServiceDefinition("flashes", {
                factory: "Nittro.Flashes.Service()",
                args: {positioner: positioner, options: {layer: config.layer, classes: config.classes}},
                run: !0
            })
        }
    });
    _context.register(FlashesExtension, "FlashesExtension")
}, {
    Neon: "Nittro.Neon.Neon",
    NeonEntity: "Nittro.Neon.NeonEntity",
    HashMap: "Utils.HashMap"
}), _context.invoke("Nittro.Routing.Bridges.RoutingDI", function (Nittro) {
    var RoutingExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        RoutingExtension.Super.call(this, containerBuilder, config)
    }, {
        STATIC: {defaults: {basePath: ""}}, load: function () {
            var builder = this._getContainerBuilder(), config = this._getConfig(RoutingExtension.defaults);
            builder.addServiceDefinition("router", {factory: "Nittro.Routing.Router()", args: config, run: !0})
        }, setup: function () {
            var builder = this._getContainerBuilder();
            builder.hasServiceDefinition("snippetManager") && builder.getServiceDefinition("snippetManager").addSetup(function (router) {
                this.on("after-update", router.matchDOM.bind(router))
            }), builder.hasServiceDefinition("history") && builder.getServiceDefinition("history").addSetup(function (router) {
                this.on("savestate popstate", router.matchURL.bind(router))
            })
        }
    });
    _context.register(RoutingExtension, "RoutingExtension")
}), _context.invoke("Nittro.Extras.CheckList.Bridges.CheckListDI", function () {
    var CheckListExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        CheckListExtension.Super.call(this, containerBuilder, config)
    }, {
        load: function () {
            this._getContainerBuilder().addFactory("checkList", "Nittro.Extras.CheckList.CheckList()")
        }
    });
    _context.register(CheckListExtension, "CheckListExtension")
}), _context.invoke("Nittro.Extras.Keymap.Bridges.KeymapDI", function () {
    var KeymapExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        KeymapExtension.Super.call(this, containerBuilder, config)
    }, {
        load: function () {
            var builder = this._getContainerBuilder();
            builder.addServiceDefinition("keymapManager", "Nittro.Extras.Keymap.Manager()"), builder.addFactory("keymap", "Nittro.Extras.Keymap.Keymap()"), builder.addFactory("tabContext", "Nittro.Extras.Keymap.TabContext()")
        }
    });
    _context.register(KeymapExtension, "KeymapExtension")
}), _context.invoke("Nittro.Extras.Dialogs.Bridges.DialogsPage", function (DOM) {
    var DialogAgent = _context.extend("Nittro.Object", function (dialogManager, snippetManager) {
        DialogAgent.Super.call(this), this._.dialogManager = dialogManager, this._.snippetManager = snippetManager
    }, {
        initTransaction: function (transaction, context) {
            var snippet, element = context.element, data = {};
            if (element) {
                (snippet = DOM.getData(element, "dialog")) && (data.snippet = snippet);
                var i, open = this._.dialogManager.getOpenDialogs();
                for (i = 0; i < open.length; i++) if (DOM.contains(open[i].getElement(), element)) {
                    data.hiding = open[i];
                    break
                }
            }
            transaction.on("dispatch", this._dispatch.bind(this, data)), transaction.on("snippets-apply", this._handleSnippets.bind(this, data))
        }, _dispatch: function (data, evt) {
            data.hiding && (data.hiding.off("hidden.cleanup"), evt.waitFor(data.hiding.hide()))
        }, _handleSnippets: function (data, evt) {
            var changeset = evt.data.changeset;
            if (data.hiding && (this._.snippetManager.cleanupDescendants(data.hiding.getElement(), changeset), this._.snippetManager.one("before-update", data.hiding.destroy.bind(data.hiding)), data.hiding = null), data.snippet && data.snippet in changeset.update) {
                var id, snippet = changeset.update[data.snippet];
                if (snippet.container) throw new Error("Dialogs from dynamic snippets aren't supported");
                delete changeset.update[data.snippet];
                for (id in changeset.remove) changeset.remove.hasOwnProperty(id) && changeset.remove[id].isDescendant && DOM.contains(snippet.element, changeset.remove[id].element) && delete changeset.remove[id];
                this._.snippetManager.one("before-update", this._createDialog.bind(this, snippet.content))
            }
        }, _createDialog: function (content) {
            var dialog, options = {}, children = DOM.getChildren(content);
            options.content = DOM.create("div"), options.buttons = null, DOM.append(options.content, children), dialog = options.content.getElementsByTagName("form").length ? this._.dialogManager.createFormDialog(options) : this._.dialogManager.createDialog(options), this._.snippetManager.one("after-update", dialog.show.bind(dialog)), dialog.one("hidden.cleanup", this._destroyDialog.bind(this, dialog))
        }, _destroyDialog: function (dialog) {
            this._.snippetManager.cleanupDescendants(dialog.getElement()), dialog.destroy()
        }
    });
    _context.register(DialogAgent, "DialogAgent")
}, {DOM: "Utils.DOM"}), _context.invoke("Nittro.Extras.Dialogs", function (Dialog, DOM, Arrays) {
    var FormDialog = _context.extend(Dialog, function (options, form) {
        FormDialog.Super.call(this, options), this.on("button", this._handleButton.bind(this)), this._.options.autoFocus && this.on("show", this._autoFocus.bind(this)), form && this.setForm(form)
    }, {
        STATIC: {
            defaults: {classes: "nittro-dialog-form", hideOnSuccess: !0, autoFocus: !0},
            setDefaults: function (defaults) {
                Arrays.mergeTree(FormDialog.defaults, defaults)
            }
        }, setForm: function (form) {
            return this._.form = form, this._.elms.form = form.getElement(), DOM.addListener(this._.elms.form, "submit", this._handleSubmit.bind(this)), this._.tabContext && this._.tabContext.addFromContainer(form.getElement(), !1, 0), this
        }, setValues: function (values) {
            return this._.form.setValues(values), this
        }, reset: function () {
            return this._.form.reset(), this
        }, getForm: function () {
            return this._.form
        }, _handleSubmit: function (evt) {
            evt.defaultPrevented || (this._.options.hideOnSuccess && this.hide(), this.trigger("success"))
        }, _handleButton: function (evt) {
            "submit" === evt.data.action ? (evt.preventDefault(), this._.form.submit()) : this._.form.reset()
        }, _autoFocus: function () {
            try {
                this._.form.getElements().item(0).focus()
            } catch (e) {
            }
        }
    });
    _context.register(FormDialog, "FormDialog")
}, {
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays"
}), _context.invoke("Nittro.Extras.Dialogs.Bridges.DialogsForms", function (Manager, FormDialog) {
    var FormDialogMixin = {
        setFormLocator: function (formLocator) {
            return this._.formLocator = formLocator, this
        }, createFormDialog: function (options) {
            var dlg = new FormDialog(options);
            this._setup(dlg), dlg.on("destroy", this._removeDialogForm.bind(this));
            var frm = dlg.getContent().getElementsByTagName("form").item(0);
            return dlg.setForm(this._.formLocator.getForm(frm)), dlg
        }, _removeDialogForm: function (evt) {
            this._.formLocator.removeForm(evt.target.getForm().getElement())
        }
    };
    _context.mixin(Manager, FormDialogMixin)
}, {
    Manager: "Nittro.Extras.Dialogs.Manager",
    FormDialog: "Nittro.Extras.Dialogs.FormDialog"
}), _context.invoke("Nittro.Extras.Dialogs.Bridges.DialogsKeymap", function (Manager) {
    var KeymapMixin = {
        setKeymapManager: function (keymapManager) {
            return this._.keymapManager = keymapManager, this.on("setup", this._setupKeymap.bind(this)), this._pushKeymap = this._pushKeymap.bind(this), this._popKeymap = this._popKeymap.bind(this), this
        }, _setupKeymap: function (evt) {
            evt.data.dialog.on("show", this._pushKeymap), evt.data.dialog.on("hide", this._popKeymap)
        }, _pushKeymap: function (evt) {
            this._.keymapManager.push(evt.target.getKeyMap(), evt.target.getTabContext())
        }, _popKeymap: function (evt) {
            this._.keymapManager.pop(evt.target.getKeyMap(), evt.target.getTabContext())
        }
    };
    _context.mixin(Manager, KeymapMixin)
}, {Manager: "Nittro.Extras.Dialogs.Manager"}), _context.invoke("Nittro.Extras.Dialogs.Bridges.DialogsDI", function () {
    var DialogsExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        DialogsExtension.Super.call(this, containerBuilder, config)
    }, {
        STATIC: {defaults: {baseZ: 1e3}}, load: function () {
            var builder = this._getContainerBuilder(), config = this._getConfig(DialogsExtension.defaults);
            builder.addServiceDefinition("dialogManager", {
                factory: "Nittro.Extras.Dialogs.Manager()",
                args: {baseZ: config.baseZ}
            }), builder.addFactory("dialog", "@dialogManager::createDialog()")
        }, setup: function () {
            var builder = this._getContainerBuilder(), def = builder.getServiceDefinition("dialogManager");
            builder.hasServiceDefinition("formLocator") && (def.addSetup("::setFormLocator()"), builder.addFactory("formDialog", "@dialogManager::createFormDialog()")), builder.hasServiceDefinition("keymapManager") && def.addSetup("::setKeymapManager()"), builder.hasServiceDefinition("page") && (builder.addServiceDefinition("dialogAgent", "Nittro.Extras.Dialogs.Bridges.DialogsPage.DialogAgent()"), builder.getServiceDefinition("page").addSetup(function (dialogAgent) {
                this.on("transaction-created", function (evt) {
                    dialogAgent.initTransaction(evt.data.transaction, evt.data.context)
                })
            }))
        }
    });
    _context.register(DialogsExtension, "DialogsExtension")
}), _context.invoke("Nittro.Extras.Confirm.Bridges.ConfirmDI", function () {
    var ConfirmExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        ConfirmExtension.Super.call(this, containerBuilder, config)
    }, {
        setup: function () {
            var builder = this._getContainerBuilder();
            builder.hasServiceDefinition("dialogManager") && builder.addFactory("confirm", "@dialogManager::createConfirm()"), builder.hasServiceDefinition("page") && (builder.addServiceDefinition("autoConfirm", {
                factory: "Nittro.Extras.Confirm.Bridges.ConfirmPage.AutoConfirm()",
                args: {options: this._getConfig()},
                run: !0
            }), builder.getServiceDefinition("page").addSetup(function (autoConfirm) {
                this.on("before-transaction", autoConfirm.handleTransaction.bind(autoConfirm))
            }))
        }
    });
    _context.register(ConfirmExtension, "ConfirmExtension")
}), _context.invoke("Nittro.Extras.Confirm.Bridges.ConfirmPage", function (DOM, Arrays) {
    var AutoConfirm = _context.extend(function (dialogManager, options) {
        this._ = {dialogManager: dialogManager, options: Arrays.mergeTree(!0, {}, AutoConfirm.defaults, options)}
    }, {
        STATIC: {defaults: {prompt: "Are you sure?", confirm: "Yes", cancel: "No"}}, handleTransaction: function (evt) {
            var elem = evt.data.context.element || null, prompt = elem ? DOM.getData(elem, "prompt") : null;
            if (prompt) {
                "string" != typeof prompt && (prompt = this._.options.prompt);
                var confirm = DOM.getData(elem, "confirm") || this._.options.confirm,
                    cancel = DOM.getData(elem, "cancel") || this._.options.cancel;
                evt.waitFor(this._.dialogManager.createConfirm(prompt, confirm, cancel).then(function (result) {
                    result || evt.preventDefault()
                }))
            }
        }
    });
    _context.register(AutoConfirm, "AutoConfirm")
}, {
    DOM: "Utils.DOM",
    Arrays: "Utils.Arrays"
}), _context.invoke("Nittro.Extras.DropZone.Bridges.DropZoneDI", function () {
    var DropZoneExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        DropZoneExtension.Super.call(this, containerBuilder, config)
    }, {
        load: function () {
            this._getContainerBuilder().addFactory("dropZone", "Nittro.Extras.DropZone.DropZone::create()")
        }
    });
    _context.register(DropZoneExtension, "DropZoneExtension")
}), _context.invoke("Nittro.Extras.Paginator.Bridges.PaginatorDI", function () {
    var PaginatorExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        PaginatorExtension.Super.call(this, containerBuilder, config)
    }, {
        load: function () {
            this._getContainerBuilder().addFactory("paginator", "Nittro.Extras.Paginator.Paginator()")
        }
    });
    _context.register(PaginatorExtension, "PaginatorExtension")
}), _context.invoke("Nittro.Extras.Storage.Bridges.StorageDI", function () {
    var StorageExtension = _context.extend("Nittro.DI.BuilderExtension", function (containerBuilder, config) {
        StorageExtension.Super.call(this, containerBuilder, config)
    }, {
        load: function () {
            var builder = this._getContainerBuilder();
            builder.addServiceDefinition("persistentStorage", "Nittro.Extras.Storage.Storage(namespace: null, persistent: true)"), builder.addServiceDefinition("sessionStorage", "Nittro.Extras.Storage.Storage(namespace: null, persistent: false)")
        }
    });
    _context.register(StorageExtension, "StorageExtension")
}), _context.invoke(function (Nittro) {/* custom helper by @mcjahudka*/
    var HistoryHelper = _context.extend(function (history, page) {
        this._ = {history: history, sessionId: Date.now()};
        this._.history.off("popstate");
        this._.history.on("popstate:default", page._handleState.bind(page));
        this._.history.on("before-savestate", this._saveSessionId.bind(this));
        this._.history.on("popstate", this._handleState.bind(this));
    }, {
        _saveSessionId: function (evt) {
            console.info("sessId", evt);
            evt.data.sessionId = this._.sessionId;
        }, _handleState: function (evt) {
            console.info("state", evt);
            if (evt.data.data.sessionId !== this._.sessionId) {
                evt.preventDefault();
                (window.history.location || window.location).href = evt.data.url;
            }
        }
    });
    _context.register(HistoryHelper, "App.HistoryHelper");
    var builder = new Nittro.DI.ContainerBuilder({
        params: {},
        extensions: {
            ajax: "Nittro.Ajax.Bridges.AjaxDI.AjaxExtension()",
            page: "Nittro.Page.Bridges.PageDI.PageExtension()",
            forms: "Nittro.Forms.Bridges.FormsDI.FormsExtension()",
            flashes: "Nittro.Flashes.Bridges.FlashesDI.FlashesExtension()",
            routing: "Nittro.Routing.Bridges.RoutingDI.RoutingExtension()",
            checklist: "Nittro.Extras.CheckList.Bridges.CheckListDI.CheckListExtension()",
            keymap: "Nittro.Extras.Keymap.Bridges.KeymapDI.KeymapExtension()",
            dialogs: "Nittro.Extras.Dialogs.Bridges.DialogsDI.DialogsExtension()",
            confirm: "Nittro.Extras.Confirm.Bridges.ConfirmDI.ConfirmExtension()",
            dropzone: "Nittro.Extras.DropZone.Bridges.DropZoneDI.DropZoneExtension()",
            paginator: "Nittro.Extras.Paginator.Bridges.PaginatorDI.PaginatorExtension()",
            storage: "Nittro.Extras.Storage.Bridges.StorageDI.StorageExtension()"
        },
        services: {historyHelper: 'App.HistoryHelper()!'},
        factories: {}
    });
    this.di = builder.createContainer(), this.di.runServices()
}), window._stack || (window._stack = []), function (stack) {
    function exec(f) {
        if ("function" == typeof f) _context.invoke(f); else if ("object" == typeof f && "undefined" != typeof f.load) {
            var q = _context.load.apply(_context, f.load);
            "function" == typeof f.then ? q.then(f.then) : f.then && f.then instanceof Array && q.then.apply(_context, f.then)
        } else _context.invoke.apply(_context, f)
    }

    for (; stack.length;) exec(stack.shift());
    stack.push = function () {
        for (var i = 0; i < arguments.length; i++) exec(arguments[i])
    }
}(window._stack);
//# sourceMappingURL=nittro.min.js.map