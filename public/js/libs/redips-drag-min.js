/*
 Copyright (c) 2008-2011, www.redips.net All rights reserved.
 Code licensed under the BSD License: http://www.redips.net/license/
 http://www.redips.net/javascript/drag-and-drop-table-content/
 Version 4.6.23
 Aug 06, 2012.
 */
var REDIPS = REDIPS || {};
REDIPS.drag = function ()
{
  var s, E, J, ya, Ma, Na, ca, da, za, Aa, Ba, Q, ja, Ca, R, ka, Z, Da, B, v, N, la, ma, na, Ea, Fa, Ga, D, x, ea, fa, oa, pa, Ha, qa, Ia, ra, ga, Ja, Oa, sa, Pa, o = null, F = 0, G = 0, ta = null, ua = null, K = 0, L = 0, O = 0, P = 0, S = 0, T = 0, t, $, M =
    [], f = [], aa, va, q, H = [], n = [
  ], y = null, C = null, X = 0, Y = 0, Qa = 0, Ra = 0, ha = !1, Ka = !1, ba = !1, g = null, u = null, z = null, j = null, w = null, I = null, k = null, A = null, U = null, i = !1, m = !1, r = "cell", wa = {div:
    [], cname:"only", other:"deny"}, Sa = {action:"deny", cname:"mark", exception:[]}, V = null, W = null, xa = null, p = null, La = 0;
  J = function () {return!1};
  s = function ()
  {
    var b, a, c, d, e, h;
    f.length = 0;
    e = y.getElementsByTagName("table");
    for (a = b = 0; b < e.length; b++) {
      if (!("redips_clone" === e[b].parentNode.id || -1 < e[b].className.indexOf("nolayout"))) {
        c = e[b].parentNode;
        d = 0;
        do {
          "TD" === c.nodeName && d++, c = c.parentNode;
        } while (c && c !== y);
        f[a] = e[b];
        f[a].redips || (f[a].redips = {});
        f[a].redips.container = y;
        f[a].redips.nestedLevel = d;
        f[a].redips.idx = a;
        d = f[a].getElementsByTagName("td");
        c = 0;
        for (h = !1; c < d.length; c++) {
          if (1 < d[c].rowSpan) {
            h = !0;
            break
          }
        }
        f[a].redips.rowspan = h;
        a++
      }
    }
    b = 0;
    for (e = aa = 1; b < f.length; b++) {
      if (0 === f[b].redips.nestedLevel) {
        f[b].redips.nestedGroup = e;
        f[b].redips.sort = 100 * aa;
        c = f[b].getElementsByTagName("table");
        for (a = 0; a < c.length; a++) {
          -1 < c[a].className.indexOf("nolayout") || (c[a].redips.nestedGroup = e, c[a].redips.sort = 100 * aa + c[a].redips.nestedLevel);
        }
        e++;
        aa++
      }
    }
  };
  ya = function (b)
  {
    var a = b || window.event, c, d;
    if (!0 === this.redips.animated) {
      return!0;
    }
    a.cancelBubble = !0;
    a.stopPropagation && a.stopPropagation();
    Ka = a.shiftKey;
    b = a.which ? a.which : a.button;
    if (Ga(a) || !a.touches && 1 !== b) {
      return!0;
    }
    if (window.getSelection) {
      window.getSelection().removeAllRanges();
    }
    else if (document.selection && "Text" === document.selection.type) {
      try {
        document.selection.empty()
      }
      catch (e) {
      }
    }
    a.touches ? (b = X = a.touches[0].clientX, d = Y = a.touches[0].clientY) : (b = X = a.clientX, d = Y = a.clientY);
    Qa = b;
    Ra = d;
    ha = !1;
    REDIPS.drag.obj_old = m = i || this;
    REDIPS.drag.obj = i = this;
    ba = -1 < i.className.indexOf("clone") ? !0 : !1;
    REDIPS.drag.table_sort && Na(i);
    y !== i.redips.container && (y = i.redips.container, s());
    -1 === i.className.indexOf("row") ? REDIPS.drag.mode = r = "cell" : (REDIPS.drag.mode = r = "row", REDIPS.drag.obj = i = ga(i));
    v();
    !ba && "cell" === r && (i.style.zIndex = 999);
    g = j = k = null;
    R();
    z = u = g;
    I = w = j;
    U = A = k;
    REDIPS.drag.source_cell = V = x("TD", i);
    REDIPS.drag.current_cell = W = V;
    REDIPS.drag.previous_cell = xa = V;
    "cell" === r ? REDIPS.drag.myhandler_clicked(W) : REDIPS.drag.myhandler_row_clicked(W);
    if (null === g || null === j || null === k) {
      if (R(), z = u = g, I = w = j, U = A = k, null === g || null === j || null === k) {
        return!0;
      }
    }
    va = q = !1;
    REDIPS.event.add(document, "mousemove", da);
    REDIPS.event.add(document, "touchmove", da);
    REDIPS.event.add(document, "mouseup", ca);
    REDIPS.event.add(document, "touchend", ca);
    i.setCapture && i.setCapture();
    null !== g && (null !== j && null !== k) && ($ = Da(g, j, k));
    c = D(f[z], "position");
    "fixed" !== c && (c = D(f[z].parentNode, "position"));
    c = B(i, c);
    o = [d - c[0], c[1] - b, c[2] - d, b - c[3]];
    y.onselectstart = function (b)
    {
      a = b || window.event;
      if (!Ga(a)) {
        a.shiftKey && document.selection.clear();
        return false
      }
    };
    return!1
  };
  Ma = function () {REDIPS.drag.myhandler_dblclicked()};
  Na = function (b)
  {
    var a;
    a = x("TABLE", b).redips.nestedGroup;
    for (b = 0; b < f.length; b++) {
      f[b].redips.nestedGroup === a && (f[b].redips.sort = 100 * aa + f[b].redips.nestedLevel);
    }
    f.sort(function (a, b) {return b.redips.sort - a.redips.sort});
    aa++
  };
  ga = function (b, a)
  {
    var c, d, e, h, f, l;
    if ("DIV" === b.nodeName) {
      return h = b, b = x("TR", b), void 0 === b.redips && (b.redips = {}), b.redips.div = h, b;
    }
    d = b;
    void 0 === d.redips && (d.redips = {});
    b = x("TABLE", b);
    ba && q && (h = d.redips.div, h.className = sa(h.className.replace("clone", "")));
    c = b.cloneNode(!0);
    ba && q && (h.className += " clone");
    e = c.rows.length - 1;
    h = "animated" === a ? 0 === e ? !0 : !1 : !0;
    for (f = e; 0 <= f; f--) {
      if (f !== d.rowIndex) {
        if (!0 === h && void 0 === a) {
          e = c.rows[f];
          for (l = 0; l < e.cells.length; l++) {
            if (-1 < e.cells[l].className.indexOf("rowhandler")) {
              h = !1;
              break
            }
          }
        }
        c.deleteRow(f)
      }
    }
    q || (d.redips.empty_row = h);
    c.redips = {};
    c.redips.container = b.redips.container;
    c.redips.source_row = d;
    Oa(d, c.rows[0]);
    Ea(d, c.rows[0]);
    document.getElementById("redips_clone").appendChild(c);
    d = B(d, "fixed");
    c.style.position = "fixed";
    c.style.top = d[0] + "px";
    c.style.left = d[3] + "px";
    c.style.width = d[1] - d[3] + "px";
    return c
  };
  Ja = function (b, a, c)
  {
    var d = f[b], b = d.rows[0].parentNode, e = !1, h, ia, l, j;
    j = function ()
    {
      m.redips.empty_row ? ra(m, "empty", REDIPS.drag.row_empty_color) : (ia = x("TABLE", ia), ia.deleteRow(l))
    };
    void 0 === c ? c = i : e = !0;
    ia = c.redips.source_row;
    l = ia.rowIndex;
    h = c.getElementsByTagName("tr")[0];
    c.parentNode.removeChild(c);
    !1 !== REDIPS.drag.myhandler_row_dropped_before(l) && (!e && -1 < p.className.indexOf(REDIPS.drag.trash_cname) ? q ? REDIPS.drag.myhandler_row_deleted() :
                                                                                                                     REDIPS.drag.trash_ask_row ?
                                                                                                                     confirm("Are you sure you want to delete row?") ?
                                                                                                                     (j(), REDIPS.drag.myhandler_row_deleted()) :
                                                                                                                     (delete m.redips.empty_row, REDIPS.drag.myhandler_row_undeleted()) :
                                                                                                                     (j(), REDIPS.drag.myhandler_row_deleted()) :
                                                           ((e || !q) && j(), a < d.rows.length ?
                                                                              g === z || "before" === REDIPS.drag.row_position ? (b.insertBefore(h, d.rows[a]), a = d.rows[a + 1]) :
                                                                              (b.insertBefore(h, d.rows[a].nextSibling), a = d.rows[a]) :
                                                                              (b.appendChild(h), a = d.rows[0]), a && (a.redips && a.redips.empty_row) && b.deleteRow(a.rowIndex), delete h.redips.empty_row, e || REDIPS.drag.myhandler_row_dropped(p)), 0 < h.getElementsByTagName("table").length && s())
  };
  Oa = function (b, a)
  {
    var c, d, e, h = [], f = [];
    h[0] = b.getElementsByTagName("input");
    h[1] = b.getElementsByTagName("textarea");
    h[2] = b.getElementsByTagName("select");
    f[0] = a.getElementsByTagName("input");
    f[1] = a.getElementsByTagName("textarea");
    f[2] = a.getElementsByTagName("select");
    for (c = 0; c < h.length; c++) {
      for (d = 0; d < h[c].length; d++) {
        switch (e = h[c][d].type, e) {
          case "text":
          case "textarea":
          case "password":
            f[c][d].value = h[c][d].value;
            break;
          case "radio":
          case "checkbox":
            f[c][d].checked = h[c][d].checked;
            break;
          case "select-one":
            f[c][d].selectedIndex = h[c][d].selectedIndex;
            break;
          case "select-multiple":
            for (e = 0; e < h[c][d].options.length; e++) {
              f[c][d].options[e].selected = h[c][d].options[e].selected
            }
        }
      }
    }
  };
  ca = function (b)
  {
    var a = b || window.event, c, d, e, b = a.clientX;
    e = a.clientY;
    S = T = 0;
    i.releaseCapture && i.releaseCapture();
    REDIPS.event.remove(document, "mousemove", da);
    REDIPS.event.remove(document, "touchmove", da);
    REDIPS.event.remove(document, "mouseup", ca);
    REDIPS.event.remove(document, "touchend", ca);
    y.onselectstart = null;
    Ba(i);
    ta = document.documentElement.scrollWidth;
    ua = document.documentElement.scrollHeight;
    S = T = 0;
    if (q && "cell" === r && (null === g || null === j || null === k)) {
      i.parentNode.removeChild(i), H[m.id] -= 1, REDIPS.drag.myhandler_notcloned();
    }
    else if (null === g || null === j || null === k) {
      REDIPS.drag.myhandler_notmoved();
    }
    else {
      g < f.length ? (a = f[g], REDIPS.drag.target_cell = p = a.rows[j].cells[k], Z(g, j, k, $), c = g, d = j) :
      null === u || null === w || null === A ? (a = f[z], REDIPS.drag.target_cell = p = a.rows[I].cells[U], Z(z, I, U, $), c = z, d = I) :
      (a = f[u], REDIPS.drag.target_cell = p = a.rows[w].cells[A], Z(u, w, A, $), c = u, d = w);
      if ("row" === r) {
        if (va) {
          if (z === c && I === d) {
            a = i.getElementsByTagName("tr")[0];
            m.style.backgroundColor = a.style.backgroundColor;
            for (b = 0; b < a.cells.length; b++) {
              m.cells[b].style.backgroundColor = a.cells[b].style.backgroundColor;
            }
            i.parentNode.removeChild(i);
            delete m.redips.empty_row;
            q ? REDIPS.drag.myhandler_row_notcloned() : REDIPS.drag.myhandler_row_dropped_source(p)
          }
          else {
            Ja(c, d);
          }
        }
        else {
          REDIPS.drag.myhandler_row_notmoved();
        }
      }
      else if (!q && !ha) {
        REDIPS.drag.myhandler_notmoved();
      }
      else if (q && z === g && I === j && U === k) {
        i.parentNode.removeChild(i), H[m.id] -= 1, REDIPS.drag.myhandler_notcloned();
      }
      else if (q && !0 === REDIPS.drag.delete_cloned && (b < a.redips.offset[3] || b > a.redips.offset[1] || e < a.redips.offset[0] || e > a.redips.offset[2])) {
        i.parentNode.removeChild(i), H[m.id] -= 1, REDIPS.drag.myhandler_notcloned();
      }
      else if (-1 < p.className.indexOf(REDIPS.drag.trash_cname)) {
        i.parentNode.removeChild(i), REDIPS.drag.trash_ask ? setTimeout(function ()
                                                                        {
                                                                          if (confirm("Are you sure you want to delete?")) {
                                                                            Aa();
                                                                          }
                                                                          else {
                                                                            if (!q) {
                                                                              f[z].rows[I].cells[U].appendChild(i);
                                                                              v()
                                                                            }
                                                                            REDIPS.drag.myhandler_undeleted()
                                                                          }
                                                                        }, 20) : Aa();
      }
      else if ("switch" === REDIPS.drag.drop_option) {
        a = REDIPS.drag.myhandler_dropped_before(p);
        if (!1 !== a) {
          a = !0;
          i.parentNode.removeChild(i);
          c = p.getElementsByTagName("div");
          d = c.length;
          for (b = 0; b < d; b++) {
            void 0 !== c[0] && (REDIPS.drag.obj_old = m = c[0], V.appendChild(m), Q(m));
          }
          d && REDIPS.drag.myhandler_switched()
        }
        za(a)
      }
      else {
        "overwrite" === REDIPS.drag.drop_option && oa(p), za();
      }
      "cell" === r && 0 < i.getElementsByTagName("table").length && s();
      v()
    }
    u = w = A = null
  };
  za = function (b)
  {
    void 0 === b && (b = REDIPS.drag.myhandler_dropped_before(p));
    !1 !== b ? ("shift" === REDIPS.drag.drop_option && Pa(p) && pa(V, p), "top" === REDIPS.drag.multiple_drop && p.hasChildNodes() ? p.insertBefore(i, p.firstChild) :
                                                                          p.appendChild(i), Q(i), REDIPS.drag.myhandler_dropped(p), q && (REDIPS.drag.myhandler_cloned_dropped(p), Fa())) :
    q && i.parentNode.removeChild(i)
  };
  Q = function (b, a) {!1 === a ? (b.onmousedown = null, b.ontouchstart = null, b.ondblclick = null) : (b.onmousedown = ya, b.ontouchstart = ya, b.ondblclick = Ma)};
  Ba = function (b)
  {
    b.style.top = "";
    b.style.left = "";
    b.style.position = "";
    b.style.zIndex = ""
  };
  Aa = function ()
  {
    var b;
    q && Fa();
    if ("shift" === REDIPS.drag.drop_option && REDIPS.drag.shift_after) {
      switch (REDIPS.drag.shift_option) {
        case "vertical2":
          b = "lastInColumn";
          break;
        case "horizontal2":
          b = "lastInRow";
          break;
        default:
          b = "last"
      }
      pa(V, ea(b, V)[2])
    }
    REDIPS.drag.myhandler_deleted(q)
  };
  da = function (b)
  {
    var b = b || window.event, a = REDIPS.drag.bound, c, d, e, h;
    b.touches ? (d = X = b.touches[0].clientX, e = Y = b.touches[0].clientY) : (d = X = b.clientX, e = Y = b.clientY);
    c = Math.abs(Qa - d);
    h = Math.abs(Ra - e);
    if (!va) {
      if ("cell" === r && (ba || !0 === REDIPS.drag.clone_shiftKey && Ka)) {
        REDIPS.drag.obj_old = m = i, REDIPS.drag.obj = i = na(i, !0), q = !0, REDIPS.drag.myhandler_cloned();
      }
      else {
        if ("row" === r) {
          if (ba || !0 === REDIPS.drag.clone_shiftKey_row && Ka) {
            q = !0;
          }
          REDIPS.drag.obj_old = m = i;
          REDIPS.drag.obj = i = ga(i);
          i.style.zIndex = 999
        }
        i.setCapture && i.setCapture();
        i.style.position = "fixed";
        v();
        R();
        "row" === r && (q ? REDIPS.drag.myhandler_row_cloned() : REDIPS.drag.myhandler_row_moved())
      }
      ka();
      d > F - o[1] && (i.style.left = F - (o[1] + o[3]) + "px");
      e > G - o[2] && (i.style.top = G - (o[0] + o[2]) + "px")
    }
    va = !0;
    if ("cell" === r && (7 < c || 7 < h) && !ha) {
      ha = !0, ka(), REDIPS.drag.myhandler_moved();
    }
    d > o[3] && d < F - o[1] && (i.style.left = d - o[3] + "px");
    e > o[0] && e < G - o[2] && (i.style.top = e - o[0] + "px");
    if (d < C[1] && d > C[3] && e < C[2] && e > C[0] && 0 === S && 0 === T && (n.containTable || d < n[3] || d > n[1] || e < n[0] || e > n[2])) {
      R(), ja();
    }
    if (REDIPS.drag.autoscroll) {
      K = a - (F / 2 > d ? d - o[3] : F - d - o[1]);
      if (0 < K) {
        if (K > a && (K = a), c = N()[0], K *= d < F / 2 ? -1 : 1, !(0 > K && 0 >= c || 0 < K && c >= ta - F) && 0 === S++) {
          REDIPS.event.remove(window, "scroll", v), la(window)
        }
      }
      else {
        K = 0;
      }
      L = a - (G / 2 > e ? e - o[0] : G - e - o[2]);
      if (0 < L) {
        if (L > a && (L = a), c = N()[1], L *= e < G / 2 ? -1 : 1, !(0 > L && 0 >= c || 0 < L && c >= ua - G) && 0 === T++) {
          REDIPS.event.remove(window, "scroll", v), ma(window)
        }
      }
      else {
        L = 0;
      }
      for (h = 0; h < M.length; h++) {
        if (c = M[h], c.autoscroll && d < c.offset[1] && d > c.offset[3] && e < c.offset[2] && e > c.offset[0]) {
          O = a - (c.midstX > d ? d - o[3] - c.offset[3] : c.offset[1] - d - o[1]);
          0 < O ? (O > a && (O = a), O *= d < c.midstX ? -1 : 1, 0 === S++ && (REDIPS.event.remove(c.div, "scroll", v), la(c.div))) : O = 0;
          P = a - (c.midstY > e ? e - o[0] - c.offset[0] : c.offset[2] - e - o[2]);
          0 < P ? (P > a && (P = a), P *= e < c.midstY ? -1 : 1, 0 === T++ && (REDIPS.event.remove(c.div, "scroll", v), ma(c.div))) : P = 0;
          break
        }
        else {
          O = P = 0
        }
      }
    }
    b.cancelBubble = !0;
    b.stopPropagation && b.stopPropagation()
  };
  ja = function ()
  {
    if (g < f.length && (g !== u || j !== w || k !== A)) {
      null !== u && (null !== w && null !== A) && (Z(u, w, A, $), REDIPS.drag.previous_cell = xa = f[u].rows[w].cells[A], REDIPS.drag.current_cell = W = f[g].rows[j].cells[k], "switching" === REDIPS.drag.drop_option && "cell" === r && (fa(W, xa), v(), R()),
        "cell" === r ? REDIPS.drag.myhandler_changed(W) : "row" === r && (g !== u || j !== w) && REDIPS.drag.myhandler_row_changed(W)), ka()
    }
  };
  Ca = function ()
  {
    if ("number" === typeof window.innerWidth) {
      F = window.innerWidth, G = window.innerHeight;
    }
    else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
      F = document.documentElement.clientWidth, G = document.documentElement.clientHeight;
    }
    else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
      F = document.body.clientWidth, G = document.body.clientHeight;
    }
    ta = document.documentElement.scrollWidth;
    ua = document.documentElement.scrollHeight;
    v()
  };
  R = function ()
  {
    var b, a, c, d, e, h;
    c = [];
    b = function () {null !== u && (null !== w && null !== A) && (g = u, j = w, k = A)};
    a = X;
    h = Y;
    for (g = 0; g < f.length; g++) {
      if (!1 !== f[g].redips.enabled && (c[0] = f[g].redips.offset[0], c[1] = f[g].redips.offset[1], c[2] = f[g].redips.offset[2], c[3] = f[g].redips.offset[3], void 0 !== f[g].sca && (c[0] =
        c[0] > f[g].sca.offset[0] ? c[0] : f[g].sca.offset[0], c[1] = c[1] < f[g].sca.offset[1] ? c[1] : f[g].sca.offset[1], c[2] = c[2] < f[g].sca.offset[2] ? c[2] :
                                                                                                                                    f[g].sca.offset[2], c[3] =
        c[3] > f[g].sca.offset[3] ? c[3] : f[g].sca.offset[3]), c[3] < a && a < c[1] && c[0] < h && h < c[2])) {
        c = f[g].redips.row_offset;
        for (j = 0; j < c.length - 1; j++) {
          if (void 0 !== c[j]) {
            n[0] = c[j][0];
            if (void 0 !== c[j + 1]) {
              n[2] = c[j + 1][0];
            }
            else {
              for (d = j + 2; d < c.length; d++) {
                if (void 0 !== c[d]) {
                  n[2] = c[d][0];
                  break
                }
              }
            }
            if (h <= n[2]) {
              break
            }
          }
        }
        d = j;
        j === c.length - 1 && (n[0] = c[j][0], n[2] = f[g].redips.offset[2]);
        do {
          for (k = e = f[g].rows[j].cells.length - 1; 0 <= k && !(n[3] = c[j][3] + f[g].rows[j].cells[k].offsetLeft, n[1] = n[3] + f[g].rows[j].cells[k].offsetWidth, n[3] <= a && a <= n[1]); k--) {
            ;
          }
        } while (f[g].redips.rowspan && -1 === k && 0 < j--);
        0 > j || 0 > k ? b() : j !== d && (n[0] = c[j][0], n[2] = n[0] + f[g].rows[j].cells[k].offsetHeight, (h < n[0] || h > n[2]) && b());
        a = f[g].rows[j].cells[k];
        n.containTable = 0 < a.childNodes.length && 0 < a.getElementsByTagName("table").length ? !0 : !1;
        if (-1 === a.className.indexOf(REDIPS.drag.trash_cname)) {
          if (h = -1 < a.className.indexOf(REDIPS.drag.only.cname) ? !0 : !1, !0 === h) {
            if (-1 === a.className.indexOf(wa.div[i.id])) {
              b();
              break
            }
          }
          else if (void 0 !== wa.div[i.id] && "deny" === wa.other) {
            b();
            break
          }
          else if (h = -1 < a.className.indexOf(REDIPS.drag.mark.cname) ? !0 :
                       !1, (!0 === h && "deny" === REDIPS.drag.mark.action || !1 === h && "allow" === REDIPS.drag.mark.action) && -1 === a.className.indexOf(Sa.exception[i.id])) {
            b();
            break
          }
        }
        h = -1 < a.className.indexOf("single") ? !0 : !1;
        if ("cell" === r) {
          if (("single" === REDIPS.drag.drop_option || h) && 0 < a.childNodes.length) {
            if (1 === a.childNodes.length && 3 === a.firstChild.nodeType) {
              break;
            }
            h = !0;
            for (d = a.childNodes.length - 1; 0 <= d; d--) {
              if (a.childNodes[d].className && -1 < a.childNodes[d].className.indexOf("drag")) {
                h = !1;
                break
              }
            }
            if (!h && (null !== u && null !== w && null !== A) && (z !== g || I !== j || U !== k)) {
              b();
              break
            }
          }
          if (-1 < a.className.indexOf("rowhandler")) {
            b();
            break
          }
          if (a.parentNode.redips && a.parentNode.redips.empty_row) {
            b();
            break
          }
        }
        break
      }
    }
  };
  ka = function () {g < f.length && (null !== g && null !== j && null !== k) && ($ = Da(g, j, k), Z(g, j, k), u = g, w = j, A = k)};
  Z = function (b, a, c, d)
  {
    if ("cell" === r && ha)c = f[b].rows[a].cells[c].style, c.backgroundColor = void 0 === d ? REDIPS.drag.hover.color_td :
                                                                                d.color[0].toString(), void 0 !== REDIPS.drag.hover.border_td && (
      void 0 === d ? c.border = REDIPS.drag.hover.border_td :
      (c.borderTopWidth = d.top[0][0], c.borderTopStyle = d.top[0][1], c.borderTopColor = d.top[0][2], c.borderRightWidth = d.right[0][0], c.borderRightStyle = d.right[0][1], c.borderRightColor = d.right[0][2], c.borderBottomWidth = d.bottom[0][0], c.borderBottomStyle = d.bottom[0][1], c.borderBottomColor = d.bottom[0][2], c.borderLeftWidth = d.left[0][0], c.borderLeftStyle = d.left[0][1], c.borderLeftColor = d.left[0][2]));
    else if ("row" === r) {
      b = f[b].rows[a];
      for (a = 0; a < b.cells.length; a++)c = b.cells[a].style, c.backgroundColor = void 0 === d ? REDIPS.drag.hover.color_tr :
                                                                                    d.color[a].toString(), void 0 !== REDIPS.drag.hover.border_tr && (void 0 === d ? g === z ?
                                                                                                                                                                     j < I ?
                                                                                                                                                                     c.borderTop = REDIPS.drag.hover.border_tr :
                                                                                                                                                                     c.borderBottom = REDIPS.drag.hover.border_tr :
                                                                                                                                                                     "before" === REDIPS.drag.row_position ?
                                                                                                                                                                     c.borderTop = REDIPS.drag.hover.border_tr :
                                                                                                                                                                     c.borderBottom = REDIPS.drag.hover.border_tr :
                                                                                                                                                      (c.borderTopWidth = d.top[a][0], c.borderTopStyle = d.top[a][1], c.borderTopColor = d.top[a][2], c.borderBottomWidth = d.bottom[a][0], c.borderBottomStyle = d.bottom[a][1], c.borderBottomColor = d.bottom[a][2]))
    }
  };
  Da = function (b, a, c)
  {
    var d = {color:[], top:[], right:[], bottom:[], left:[]}, e = function (a, b)
    {
      var c = "border" + b + "Style", d = "border" + b + "Color";
      return[D(a, "border" + b + "Width"), D(a, c), D(a, d)]
    };
    if ("cell" === r)c = f[b].rows[a].cells[c], d.color[0] = c.style.backgroundColor, void 0 !== REDIPS.drag.hover.border_td && (d.top[0] = e(c, "Top"), d.right[0] = e(c, "Right"), d.bottom[0] = e(c, "Bottom"), d.left[0] = e(c, "Left"));
    else {
      b = f[b].rows[a];
      for (a = 0; a < b.cells.length; a++)c = b.cells[a], d.color[a] = c.style.backgroundColor, void 0 !== REDIPS.drag.hover.border_tr && (d.top[a] = e(c, "Top"), d.bottom[a] = e(c, "Bottom"))
    }
    return d
  };
  B = function (b, a, c)
  {
    var d = 0, e = 0, h = b;
    "fixed" !== a && (a = N(), d = 0 - a[0], e = 0 - a[1]);
    if (void 0 === c || !0 === c) {
      do d += b.offsetLeft - b.scrollLeft, e += b.offsetTop - b.scrollTop, b = b.offsetParent; while (b && "BODY" !== b.nodeName)
    }
    else {
      do d += b.offsetLeft, e += b.offsetTop, b = b.offsetParent; while (b && "BODY" !== b.nodeName)
    }
    return[e, d + h.offsetWidth, e + h.offsetHeight, d]
  };
  v = function ()
  {
    var b, a, c, d;
    for (b = 0; b < f.length; b++) {
      c = [];
      d = D(f[b], "position");
      "fixed" !== d && (d = D(f[b].parentNode, "position"));
      for (a = f[b].rows.length - 1; 0 <= a; a--)"none" !== f[b].rows[a].style.display && (c[a] = B(f[b].rows[a], d));
      f[b].redips.offset = B(f[b], d);
      f[b].redips.row_offset = c
    }
    C = B(y);
    for (b = 0; b < M.length; b++)d = D(M[b].div, "position"), a = B(M[b].div, d, !1), M[b].offset = a, M[b].midstX = (a[1] + a[3]) / 2, M[b].midstY = (a[0] + a[2]) / 2
  };
  N = function ()
  {
    var b, a;
    "number" === typeof window.pageYOffset ? (b = window.pageXOffset, a = window.pageYOffset) :
    document.body && (document.body.scrollLeft || document.body.scrollTop) ? (b = document.body.scrollLeft, a = document.body.scrollTop) :
    document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop) ?
    (b = document.documentElement.scrollLeft, a = document.documentElement.scrollTop) : b = a = 0;
    return[b, a]
  };
  la = function (b)
  {
    var a, c;
    a = X;
    c = Y;
    0 < S && (v(), R(), a < C[1] && (a > C[3] && c < C[2] && c > C[0]) && ja());
    "object" === typeof b && (t = b);
    t === window ? (b = N()[0], a = ta - F, c = K) : (b = t.scrollLeft, a = t.scrollWidth - t.clientWidth, c = O);
    0 < S && (0 > c && 0 < b || 0 < c && b < a) ?
    (t === window ? (window.scrollBy(c, 0), N(), b = parseInt(i.style.left, 10), isNaN(b)) : t.scrollLeft += c, setTimeout(la, REDIPS.drag.speed)) :
    (REDIPS.event.add(t, "scroll", v), S = 0, n = [0, 0, 0, 0])
  };
  ma = function (b)
  {
    var a, c;
    a = X;
    c = Y;
    0 < T && (v(), R(), a < C[1] && (a > C[3] && c < C[2] && c > C[0]) && ja());
    "object" === typeof b && (t = b);
    t === window ? (b = N()[1], a = ua - G, c = L) : (b = t.scrollTop, a = t.scrollHeight - t.clientHeight, c = P);
    0 < T && (0 > c && 0 < b || 0 < c && b < a) ?
    (t === window ? (window.scrollBy(0, c), N(), b = parseInt(i.style.top, 10), isNaN(b)) : t.scrollTop += c, setTimeout(ma, REDIPS.drag.speed)) :
    (REDIPS.event.add(t, "scroll", v), T = 0, n = [0, 0, 0, 0])
  };
  na = function (b, a)
  {
    var c = b.cloneNode(!0), d = c.className, e, h;
    !0 === a && (document.getElementById("redips_clone").appendChild(c), c.style.zIndex = 999, c.style.position = "fixed", e = B(b), h = B(c), c.style.top = e[0] - h[0] + "px", c.style.left = e[3] - h[3] + "px");
    c.setCapture && c.setCapture();
    d = d.replace("clone", "");
    d = d.replace(/climit(\d)_(\d+)/, "");
    c.className = sa(d);
    void 0 === H[b.id] && (H[b.id] = 0);
    c.id = b.id + "c" + H[b.id];
    H[b.id] += 1;
    Ea(b, c);
    return c
  };
  Ea = function (b, a)
  {
    var c = [], d;
    c[0] = function (a, b) {a.redips && (b.redips = {}, b.redips.enabled = a.redips.enabled, b.redips.container = a.redips.container, a.redips.enabled && Q(b))};
    c[1] = function (a, b) {a.redips && (b.redips = {}, b.redips.empty_row = a.redips.empty_row)};
    d = function (d)
    {
      var h, f, l;
      f = ["DIV", "TR"];
      h = b.getElementsByTagName(f[d]);
      f = a.getElementsByTagName(f[d]);
      for (l = 0; l < f.length; l++)c[d](h[l], f[l])
    };
    if ("DIV" === b.nodeName)c[0](b, a);
    else if ("TR" === b.nodeName)c[1](b, a);
    d(0);
    d(1)
  };
  Fa = function ()
  {
    var b, a, c;
    c = m.className;
    b = c.match(/climit(\d)_(\d+)/);
    null !== b && (a = parseInt(b[1], 10), b = parseInt(b[2], 10), b -= 1, c = c.replace(/climit\d_\d+/g, ""), 0 >= b ? (c = c.replace("clone", ""), 2 === a ?
                                                                                                                                                     (c = c.replace("drag", ""), Q(m, !1), m.style.cursor = "auto", REDIPS.drag.myhandler_clonedend2()) :
                                                                                                                                                     REDIPS.drag.myhandler_clonedend1()) :
                                                                                                               c = c + " climit" + a + "_" + b, m.className = sa(c))
  };
  Ga = function (b)
  {
    var a = !1;
    b.srcElement ? (a = b.srcElement.nodeName, b = b.srcElement.className) : (a = b.target.nodeName, b = b.target.className);
    switch (a) {
      case "A":
      case "INPUT":
      case "SELECT":
      case "OPTION":
      case "TEXTAREA":
        a = !0;
        break;
      default:
        a = /\bnodrag\b/i.test(b) ? !0 : !1
    }
    return a
  };
  E = function (b, a, c)
  {
    var d, e = [], h = [], f, l, i, g, j = /\bdrag\b/i, k = /\bnoautoscroll\b/i;
    l = REDIPS.drag.opacity_disabled;
    !0 === b || "init" === b ? (f = REDIPS.drag.border, i = "move", g = !0) : (f = REDIPS.drag.border_disabled, i = "auto", g = !1);
    void 0 === a ? e = y.getElementsByTagName("div") :
    "subtree" === c ? e = "string" === typeof a ? document.getElementById(a).getElementsByTagName("div") : a.getElementsByTagName("div") :
    e[0] = "string" === typeof a ? document.getElementById(a) : a;
    for (c = a = 0; a < e.length; a++)if (j.test(e[a].className))"init" === b || void 0 === e[a].redips ? (e[a].redips = {}, e[a].redips.container = y) :
                                                                 !0 === b && "number" === typeof l ? (e[a].style.opacity = "", e[a].style.filter = "") :
                                                                 !1 === b && "number" === typeof l && (e[a].style.opacity = l / 100, e[a].style.filter = "alpha(opacity=" + l + ")"), Q(e[a], g), e[a].style.borderStyle = f, e[a].style.cursor = i, e[a].redips.enabled = g;
    else if ("init" === b && (h = D(e[a], "overflow"), "visible" !== h)) {
      REDIPS.event.add(e[a], "scroll", v);
      h = D(e[a], "position");
      d = B(e[a], h, !1);
      h = k.test(e[a].className) ? !1 : !0;
      M[c] = {div:e[a], offset:d, midstX:(d[1] + d[3]) / 2, midstY:(d[0] + d[2]) / 2, autoscroll:h};
      h = e[a].getElementsByTagName("table");
      for (d = 0; d < h.length; d++)h[d].sca = M[c];
      c++
    }
  };
  D = function (b, a)
  {
    var c;
    b && b.currentStyle ? c = b.currentStyle[a] : b && window.getComputedStyle && (c = document.defaultView.getComputedStyle(b, null)[a]);
    return c
  };
  x = function (b, a, c)
  {
    a = a.parentNode;
    for (void 0 === c && (c = 0); a && a.nodeName !== b;)if ((a = a.parentNode) && a.nodeName === b && 0 < c)c--, a = a.parentNode;
    return a
  };
  ea = function (b, a)
  {
    var c = x("TABLE", a), d, e;
    switch (b) {
      case "firstInColumn":
        d = 0;
        e = a.cellIndex;
        break;
      case "firstInRow":
        d = a.parentNode.rowIndex;
        e = 0;
        break;
      case "lastInColumn":
        d = c.rows.length - 1;
        e = a.cellIndex;
        break;
      case "lastInRow":
        d = a.parentNode.rowIndex;
        e = c.rows[d].cells.length - 1;
        break;
      case "last":
        d = c.rows.length - 1;
        e = c.rows[d].cells.length - 1;
        break;
      default:
        d = e = 0
    }
    return[d, e, c.rows[d].cells[e]]
  };
  fa = function (b, a, c)
  {
    var d, e, f;
    d = function (a, b)
    {
      var c = REDIPS.drag.get_position(b);
      REDIPS.drag.move_object({obj:a, target:c, callback:function (a)
      {
        La--;
        0 === La && (a = REDIPS.drag.find_parent("TABLE", a), REDIPS.drag.enable_table(!0, a))
      }})
    };
    if (b !== a && !("object" !== typeof b || "object" !== typeof a))if (e = b.childNodes.length, "animation" === c) {
      0 < e && (c = x("TABLE", a), REDIPS.drag.enable_table(!1, c));
      for (c = 0; c < e; c++)1 === b.childNodes[c].nodeType && "DIV" === b.childNodes[c].nodeName && (La++, d(b.childNodes[c], a))
    }
    else for (d = c = 0; c < e; c++)1 === b.childNodes[d].nodeType && "DIV" === b.childNodes[d].nodeName ? (f = b.childNodes[d], a.appendChild(f), Q(f)) : d++
  };
  oa = function (b)
  {
    var a, c;
    if ("TD" !== b.nodeName)return!1;
    c = b.childNodes.length;
    for (a = 0; a < c; a++)b.removeChild(b.childNodes[0])
  };
  pa = function (b, a)
  {
    var c, d, e, f, i, l, g, j, k;
    if (b !== a) {
      i = REDIPS.drag.shift_option;
      e = [b.parentNode.rowIndex, b.cellIndex];
      f = [a.parentNode.rowIndex, a.cellIndex];
      c = x("TABLE", b);
      d = x("TABLE", a);
      l = d.rows.length - 1;
      g = d.rows[0].cells.length - 1;
      switch (i) {
        case "vertical2":
          c = c === d && b.cellIndex === a.cellIndex ? e : ea("lastInColumn", a);
          break;
        case "horizontal2":
          c = c === d && b.parentNode.rowIndex === a.parentNode.rowIndex ? e : ea("lastInRow", a);
          break;
        default:
          c = c === d ? e : [l, g]
      }
      "vertical1" === i || "vertical2" === i ? (i = 1E3 * c[1] + c[0] < 1E3 * f[1] + f[0] ? 1 : -1, k = l, e = 0, j = 1) :
      (i = 1E3 * c[0] + c[1] < 1E3 * f[0] + f[1] ? 1 : -1, k = g, e = 1, j = 0);
      for (; c[0] !== f[0] || c[1] !== f[1];)g = d.rows[c[0]].cells[c[1]], c[e] += i, 0 > c[e] ? (c[e] = k, c[j]--) : c[e] > k && (c[e] = 0, c[j]++), l = d.rows[c[0]].cells[c[1]],
        REDIPS.drag.animation_shift ? fa(l, g, "animation") : fa(l, g)
    }
  };
  Ha = function (b, a)
  {
    var c = (a.k1 - a.k2 * b) * (a.k1 - a.k2 * b), d, b = b + REDIPS.drag.animation_step * (4 - 3 * c) * a.direction;
    d = a.m * b + a.b;
    "horizontal" === a.type ? (a.obj.style.left = b + "px", a.obj.style.top = d + "px") : (a.obj.style.left = d + "px", a.obj.style.top = b + "px");
    b < a.last && 0 < a.direction || b > a.last && 0 > a.direction ? setTimeout(function () {Ha(b, a)}, REDIPS.drag.animation_pause * c) :
    (Ba(a.obj), a.obj.redips.animated = !1, "cell" === a.mode ? (!0 === a.overwrite && oa(a.target_cell), a.target_cell.appendChild(a.obj), Q(a.obj)) :
                                            Ja(qa(a.target[0]), a.target[1], a.obj), "function" === typeof a.callback && a.callback(a.obj))
  };
  Ia = function (b)
  {
    var a, c, d;
    a = [];
    a = c = d = -1;
    if (void 0 === b)a = g < f.length ? f[g].redips.idx : null === u || null === w || null === A ? f[z].redips.idx : f[u].redips.idx, c = f[z].redips.idx, a = [a, j, k, c, I, U];
    else {
      if (b = "string" === typeof b ? document.getElementById(b) :
              b)"TD" !== b.nodeName && (b = x("TD", b)), b && "TD" === b.nodeName && (a = b.cellIndex, c = b.parentNode.rowIndex, b = x("TABLE", b), d = b.redips.idx);
      a = [d, c, a]
    }
    return a
  };
  qa = function (b)
  {
    var a;
    for (a = 0; a < f.length && f[a].redips.idx !== b; a++);
    return a
  };
  sa = function (b)
  {
    void 0 !== b && (b = b.replace(/^\s+|\s+$/g, "").replace(/\s{2,}/g, " "));
    return b
  };
  Pa = function (b)
  {
    var a;
    for (a = 0; a < b.childNodes.length; a++)if (1 === b.childNodes[a].nodeType)return!0;
    return!1
  };
  ra = function (b, a, c)
  {
    var d, e;
    "string" === typeof b && (b = document.getElementById(b), b = x("TABLE", b));
    if ("TR" === b.nodeName) {
      b = b.getElementsByTagName("td");
      for (d = 0; d < b.length; d++)if (b[d].style.backgroundColor = c ? c : "", "empty" === a)b[d].innerHTML = "";
      else for (e = 0; e < b[d].childNodes.length; e++)1 === b[d].childNodes[e].nodeType && (b[d].childNodes[e].style.opacity = a / 100, b[d].childNodes[e].style.filter = "alpha(opacity=" + a + ")")
    }
    else b.style.opacity = a / 100, b.style.filter = "alpha(opacity=" + a + ")"
  };
  return{obj:               i, obj_old:m, mode:r, source_cell:V, previous_cell:xa, current_cell:W, target_cell:p, hover:{color_td:"#E7AB83", color_tr:"#E7AB83"}, autoscroll:!0, bound:25, speed:20, only:wa, mark:Sa, border:"solid", border_disabled:"dotted", opacity_disabled:void 0, trash_cname:"trash", trash_ask:!0, trash_ask_row:!0, save_pname:"p", drop_option:"multiple", shift_option:"horizontal1", multiple_drop:"bottom", delete_cloned:!0, delete_shifted:!1, clone_shiftKey:!1,
    clone_shiftKey_row:     !1, animation_pause:20, animation_step:2, animation_shift:!1, shift_after:!0, row_empty_color:"White", row_position:"before", table_sort:!0, init:function (b)
    {
      var a;
      void 0 === b && (b = "drag");
      y = document.getElementById(b);
      document.getElementById("redips_clone") || (b = document.createElement("div"), b.id = "redips_clone", b.style.width = b.style.height = "1px", y.appendChild(b));
      E("init");
      s();
      Ca();
      REDIPS.event.add(window, "resize", Ca);
      a = y.getElementsByTagName("img");
      for (b = 0; b < a.length; b++)REDIPS.event.add(a[b], "mousemove", J), REDIPS.event.add(a[b], "touchmove", J);
      REDIPS.event.add(window, "scroll", v)
    }, enable_drag:         E, enable_table:function (b, a)
    {
      var c;
      if ("object" === typeof a && "TABLE" === a.nodeName)a.redips.enabled = b;
      else for (c = 0; c < f.length; c++)-1 < f[c].className.indexOf(a) && (f[c].redips.enabled = b)
    }, clone_div:           na, save_content:function (b, a)
    {
      var c = "", d, e, f, i, l, g, j, k = [], m = REDIPS.drag.save_pname;
      "string" === typeof b && (b = document.getElementById(b));
      if (void 0 !== b && "object" === typeof b && "TABLE" === b.nodeName) {
        d = b.rows.length;
        for (l = 0; l < d; l++) {
          e = b.rows[l].cells.length;
          for (g = 0; g < e; g++)if (f = b.rows[l].cells[g], 0 < f.childNodes.length)for (j = 0; j < f.childNodes.length; j++)i = f.childNodes[j], "DIV" === i.nodeName && -1 < i.className.indexOf("drag") && (c += m + "[]=" + i.id + "_" + l + "_" + g + "&", k.push(
            [i.id, l, g]))
        }
        c = "json" === a && 0 < k.length ? JSON.stringify(k) : c.substring(0, c.length - 1)
      }
      return c
    }, relocate:            fa, empty_cell:oa, move_object:function (b)
    {
      var a = {direction:1}, c, d, e, h, i, g;
      a.callback = b.callback;
      a.overwrite = b.overwrite;
      "string" === typeof b.id ? a.obj = a.obj_old = document.getElementById(b.id) : "object" === typeof b.obj && "DIV" === b.obj.nodeName && (a.obj = a.obj_old = b.obj);
      if ("row" === b.mode) {
        a.mode = "row";
        g = qa(b.source[0]);
        i = b.source[1];
        m = a.obj_old = f[g].rows[i];
        if (m.redips && !0 === m.redips.empty_row)return!1;
        a.obj = ga(a.obj_old, "animated")
      }
      else if (a.obj && -1 < a.obj.className.indexOf("row")) {
        a.mode = "row";
        a.obj = a.obj_old = m = x("TR", a.obj);
        if (m.redips && !0 === m.redips.empty_row)return!1;
        a.obj = ga(a.obj_old, "animated")
      }
      else a.mode = "cell";
      if (!("object" !== typeof a.obj || null === a.obj))return a.obj.style.zIndex = 999, y !== a.obj.redips.container && (y = a.obj.redips.container, s()), g = B(a.obj), e = g[1] - g[3], h = g[2] - g[0], c = g[3], d = g[0], !0 === b.clone && "cell" === a.mode && (a.obj = na(a.obj, !0), REDIPS.drag.myhandler_cloned(a.obj)), void 0 === b.target && (b.target = Ia()), a.target = b.target, g = qa(b.target[0]), i = b.target[1], b = b.target[2], i > f[g].rows.length - 1 && (i = f[g].rows.length - 1), a.target_cell = f[g].rows[i].cells[b],
        "cell" === a.mode ? (g = B(a.target_cell), i = g[1] - g[3], b = g[2] - g[0], e = g[3] + (i - e) / 2, h = g[0] + (b - h) / 2) :
        (g = B(f[g].rows[i]), e = g[3], h = g[0]), g = e - c, b = h - d, a.obj.style.position = "fixed", Math.abs(g) > Math.abs(b) ?
                                                                                                         (a.type = "horizontal", a.m = b / g, a.b = d - a.m * c, a.k1 = (c + e) / (c - e), a.k2 = 2 / (c - e), c > e && (a.direction = -1), g = c, a.last = e) :
                                                                                                         (a.type = "vertical", a.m = g / b, a.b = c - a.m * d, a.k1 = (d + h) / (d - h), a.k2 = 2 / (d - h), d > h && (a.direction = -1), g = d, a.last = h), a.obj.redips.animated = !0, Ha(g, a),
        [a.obj, a.obj_old]
    }, shift_cells:         pa, delete_object:function (b)
    {
      "object" === typeof b && "DIV" === b.nodeName ? b.parentNode.removeChild(b) : "string" === typeof b && (b = document.getElementById(b)) && b.parentNode.removeChild(b)
    },
    get_position:           Ia, row_opacity:ra, row_empty:function (b, a, c)
    {
      b = document.getElementById(b).rows[a];
      void 0 === c && (c = REDIPS.drag.row_empty_color);
      void 0 === b.redips && (b.redips = {});
      b.redips.empty_row = !0;
      ra(b, "empty", c)
    }, getScrollPosition:   N, get_style:D, find_parent:x, find_cell:ea, myhandler_clicked:function () {}, myhandler_dblclicked:function () {}, myhandler_moved:function () {}, myhandler_notmoved:function () {}, myhandler_dropped:function () {}, myhandler_dropped_before:function () {}, myhandler_switched:function () {}, myhandler_changed:function () {},
    myhandler_cloned:       function () {}, myhandler_cloned_dropped:function () {}, myhandler_clonedend1:function () {}, myhandler_clonedend2:function () {}, myhandler_notcloned:function () {}, myhandler_deleted:function () {}, myhandler_undeleted:function () {}, myhandler_row_clicked:function () {}, myhandler_row_moved:function () {}, myhandler_row_notmoved:function () {}, myhandler_row_dropped:function () {}, myhandler_row_dropped_before:function () {}, myhandler_row_dropped_source:function () {}, myhandler_row_changed:function () {}, myhandler_row_cloned:function () {},
    myhandler_row_notcloned:function () {}, myhandler_row_deleted:function () {}, myhandler_row_undeleted:function () {}}
}();
REDIPS.event || (REDIPS.event = function ()
{
  return{add:function (s, E, J)
  {
    s.addEventListener ? s.addEventListener(E, J, !1) : s.attachEvent ? s.attachEvent("on" + E, J) : s["on" + E] = J
  }, remove: function (s, E, J)
  {
    s.removeEventListener ? s.removeEventListener(E, J, !1) : s.detachEvent ? s.detachEvent("on" + E, J) : s["on" + E] = null
  }}
}());
