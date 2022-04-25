let util = {

};
util.title = function (title) {
    title = title ? title + ' - SVNAdmin' : 'SVNAdmin';
    window.document.title = title;
};

export default util;
