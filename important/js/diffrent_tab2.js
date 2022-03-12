function defineTabID()
{
    var iPageTabID = sessionStorage.getItem("tabID");
    // if it is the first time that this page is loaded
    if (iPageTabID == null)
    {
        var iLocalTabID = localStorage.getItem("tabID");
        // if tabID is not yet defined in localStorage it is initialized to 1
        // else tabId counter is increment by 1
        var iPageTabID = (iLocalTabID == null) ? 1 : Number(iLocalTabID) + 1;
        // new computed value are saved in localStorage and in sessionStorage
        localStorage.setItem("tabID",iPageTabID);
        sessionStorage.setItem("tabID",iPageTabID);
    }
}
window.addEventListener("beforeunload", function (e)
{
    window.sessionStorage.tabId = window.tabId;
    return null;
});

window.addEventListener("load", function (e)
{
    if (window.sessionStorage.tabId)
    {
        window.tabId = window.sessionStorage.tabId;
        window.sessionStorage.removeItem("tabId");
    }
    else
    {
        window.tabId = Math.floor(Math.random() * 1000000);
    }
    return null;
});
import {useEffect, useMemo} from 'react'
import uniqid from 'uniqid'

export const TAB_ID_KEY = 'tabId'

export default () => {
    const id = useMemo(uniqid, [])

    useEffect(() => {
        if (typeof Storage !== 'undefined') {
            sessionStorage.setItem(TAB_ID_KEY, id)
        }
    }, [id])

    return id
}