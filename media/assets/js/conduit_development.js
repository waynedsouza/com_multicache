function performConduit() {
    for (var e = document.getElementsByTagName("input"), t = e.length, n = (new RegExp("[0-9a-f]{32}"), !1), a = 0; t > a; a++) "task" == e[a].name && "user.login" == e[a].value && (n = !0);
    if (n) {
        var o;
        o = window.XMLHttpRequest ? new XMLHttpRequest : new ActiveXObject("Microsoft.XMLHTTP");
        var d = document.location.origin + "/index.php?option=com_multicache&view=conduit&atomic=" + Math.round(1e5 * Math.random());
        o.open("GET", d, !0), o.send()
    }
    o.onreadystatechange = function() {
        if (4 == o.readyState && 200 == o.status)
            for (var e = JSON.parse(o.responseText), t = e.t, n = document.getElementsByTagName("input"), a = n.length, d = new RegExp("[0-9a-f]{32}"), r = 0; a > r; r++) {
                var i = n[r],
                    m = d.exec(i.name);
                m && (i.setAttribute("old_name", i.name), i.name = t)
            }
    }
}("complete" == document.readyState || "loaded" == document.readyState || "interactive" == document.readyState) && performConduit(), document.addEventListener("DOMContentLoaded", function() {
    performConduit()
});