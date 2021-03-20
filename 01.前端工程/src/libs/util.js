let util = {};

util.title = function (title) {
    title = title ? title + ' - Svn Admin' : 'Svn Admin';
    window.document.title = title;
};

export default util;
