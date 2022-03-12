var tabID = sessionStorage.tabID && sessionStorage.closedLastTab !== '2' ? sessionStorage.tabID : sessionStorage.tabID = Math.random();
sessionStorage.closedLastTab = '2';
$(window).on('unload beforeunload', function() {
    sessionStorage.closedLastTab = '1';
});