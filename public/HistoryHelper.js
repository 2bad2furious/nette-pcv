/* custom helper by @mcjahudka */
var HistoryHelper = _context.extend(function (history, page) {
    this._ = {
        history: history,
        sessionId: Date.now()
    };

    // tohle je hotfix - nittro sluzba "page" si na popstate navazuje
    // obycejnej listener, nikoliv defaultni, takze by ho jinak neslo
    // vcas zrusit
    this._.history.off('popstate');
    this._.history.on('popstate:default', page._handleState.bind(page));

    this._.history.on('before-savestate', this._saveSessionId.bind(this));
    this._.history.on('popstate', this._handleState.bind(this));

}, {
    _saveSessionId: function (evt) {
        console.info(evt);
        evt.data.sessionId = this._.sessionId;
    },

    _handleState: function (evt) {
        console.info(evt);
        if (evt.data.data !== undefined && evt.data.data.sessionId !== this._.sessionId) {
            evt.preventDefault();
            (window.history.location || window.location).href = evt.data.url;
        }
    }
});

_context.register(HistoryHelper, 'App.HistoryHelper');
/* end of custom helper*/