(function () {
    "use strict";

    var API = "api/index.php";
    var state = {
        q: "",
        union: "",
        area: "",
        page: 1,
        limit: 20,
        total: 0,
        items: [],
        filters: null,
        selectedId: null,
        selected: null,
        searchSeq: 0,
        searchAbort: null,
        voterAbort: null,
        live: true,
        pdf: null,
        pdfLoadSeq: 0,
        pdfRenderSeq: 0,
        loadingTask: null,
        currentDoc: null,
        advOpen: false,
        adv: {
            name: "", father: "", mother: "", voter_no: "",
            address: "", occupation: "", serial_min: "", serial_max: "", page_no: ""
        }
    };

    var ADV_FIELDS = [
        ["name", "advName"], ["father", "advFather"], ["mother", "advMother"],
        ["voter_no", "advVoterNo"], ["address", "advAddress"], ["occupation", "advOccupation"],
        ["serial_min", "advSerialMin"], ["serial_max", "advSerialMax"], ["page_no", "advPage"]
    ];

    var els = {};
    var toastTimer = null;
    var pdfJsPromise = null;

    function ensurePdfJs() {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        if (pdfJsPromise) return pdfJsPromise;
        pdfJsPromise = new Promise(function (resolve, reject) {
            var script = document.createElement("script");
            script.src = "assets/pdfjs/pdf.min.js";
            script.onload = function () {
                if (!window.pdfjsLib) {
                    reject(new Error("PDF engine failed to load."));
                    return;
                }
                if (window.pdfjsLib.GlobalWorkerOptions) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = "assets/pdfjs/pdf.worker.min.js";
                }
                resolve(window.pdfjsLib);
            };
            script.onerror = function () {
                pdfJsPromise = null;
                reject(new Error("PDF engine failed to load."));
            };
            document.head.appendChild(script);
        });
        return pdfJsPromise;
    }

    function $(id) {
        return document.getElementById(id);
    }

    function esc(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function fmt(value) {
        var number = Number(value);
        if (!isFinite(number)) return "—";
        return number.toLocaleString("en-US");
    }

    function apiUrl(action, params) {
        var url = new URL(API, window.location.href);
        url.searchParams.set("action", action);
        if (params) {
            Object.keys(params).forEach(function (key) {
                var value = params[key];
                if (value === undefined || value === null || value === "") return;
                url.searchParams.set(key, value);
            });
        }
        return url.toString();
    }

    function fetchJson(url, signal) {
        return fetch(url, {
            signal: signal,
            credentials: "same-origin",
            headers: { Accept: "application/json" }
        }).then(function (response) {
            return response.json().catch(function () {
                throw new Error("The server returned an unreadable response.");
            }).then(function (payload) {
                if (!response.ok || payload.ok === false) {
                    throw new Error(payload.error || "Request failed.");
                }
                return payload;
            });
        });
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments;
            var self = this;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(self, args);
            }, wait);
        };
    }

    function toast(message) {
        var el = els.toast;
        if (!el) return;
        el.textContent = message;
        el.hidden = false;
        requestAnimationFrame(function () {
            el.classList.add("show");
        });
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            el.classList.remove("show");
            setTimeout(function () {
                el.hidden = true;
            }, 260);
        }, 2200);
    }

    function countUp(el, target) {
        if (!el) return;
        var start = performance.now();
        var duration = 900;
        function tick(now) {
            var progress = Math.min(1, (now - start) / duration);
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = fmt(Math.round(target * eased));
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    /* ---------- boot ---------- */

    function cacheElements() {
        [
            "searchForm", "q", "searchProgress", "unionFilter", "areaFilter",
            "clearSearch", "stats", "workspace", "results",
            "resultsScroll", "resultsTitle", "resultCount", "resultTime",
            "loadMore", "emptyState", "suggestAreas", "viewerPanel", "viewerClose",
            "viewerEmpty", "viewerBody", "backdrop", "toast",
            "builtAt", "vAvatar", "vName", "vStatus", "vMeta",
            "copyBtn", "dVoterNo", "dSerial", "dFather", "dMother", "dOccupation",
            "dDob", "dAddress", "dChips", "pdfFileName", "liveToggle",
            "pdfToolbar", "pagePrev", "pageNext", "pageNow", "pageTotal",
            "zoomIn", "zoomOut", "zoomFit", "jumpVoter", "pdfStage", "pdfOff",
            "pdfLoading", "pdfError", "retryPdf", "enableLive", "pdfCanvasWrap",
            "pdfCanvas", "pdfOverlay", "pdfHighlight", "hlTag",
            "advToggle", "advPanel", "advCount", "advClear",
            "advName", "advFather", "advMother", "advVoterNo", "advAddress",
            "advOccupation", "advSerialMin", "advSerialMax", "advPage"
        ].forEach(function (id) {
            els[id] = $(id);
        });
    }

    function advUrlKey(field) {
        return field === "page_no" ? "vpage" : field;
    }

    function readUrl() {
        var params = new URLSearchParams(window.location.search);
        state.q = (params.get("q") || "").trim().slice(0, 120);
        state.union = params.get("union") || "";
        state.area = params.get("area") || "";
        ADV_FIELDS.forEach(function (pair) {
            state.adv[pair[0]] = (params.get(advUrlKey(pair[0])) || "").trim();
        });
        state.advOpen = params.get("adv") === "1" || countAdv(state.adv) > 0;
        var id = parseInt(params.get("id") || "", 10);
        return isFinite(id) && id > 0 ? id : null;
    }

    function writeUrl() {
        var params = new URLSearchParams();
        if (state.q) params.set("q", state.q);
        if (state.union) params.set("union", state.union);
        if (state.area) params.set("area", state.area);
        if (state.advOpen) params.set("adv", "1");
        ADV_FIELDS.forEach(function (pair) {
            var value = state.adv[pair[0]];
            if (value) params.set(advUrlKey(pair[0]), value);
        });
        if (state.selectedId) params.set("id", String(state.selectedId));
        var query = params.toString();
        var next = window.location.pathname + (query ? "?" + query : "");
        window.history.replaceState(null, "", next);
    }

    function syncControls() {
        els.q.value = state.q;
        els.unionFilter.value = state.union;
        populateAreas();
        els.areaFilter.value = state.area;
        syncAdvInputs();
        setAdvOpen(state.advOpen);
        updateClearButton();
    }

    function advFromInputs() {
        var adv = {};
        ADV_FIELDS.forEach(function (pair) {
            adv[pair[0]] = els[pair[1]].value.trim();
        });
        return adv;
    }

    function countAdv(adv) {
        return ADV_FIELDS.filter(function (pair) {
            return !!adv[pair[0]];
        }).length;
    }

    function updateAdvBadge() {
        var count = countAdv(advFromInputs());
        els.advCount.textContent = String(count);
        els.advCount.hidden = count === 0;
        updateClearButton();
    }

    function updateClearButton() {
        var active = els.q.value.trim() !== "" || !!state.union || !!state.area ||
            countAdv(advFromInputs()) > 0;
        els.clearSearch.hidden = !active;
    }

    function setAdvOpen(open) {
        state.advOpen = !!open;
        els.advPanel.hidden = !state.advOpen;
        els.advToggle.setAttribute("aria-expanded", state.advOpen ? "true" : "false");
    }

    function syncAdvInputs() {
        ADV_FIELDS.forEach(function (pair) {
            els[pair[1]].value = state.adv[pair[0]] || "";
        });
        updateAdvBadge();
    }

    function loadMeta() {
        Promise.all([
            fetchJson(apiUrl("stats")),
            fetchJson(apiUrl("filters"))
        ]).then(function (results) {
            var stats = results[0];
            state.filters = results[1];
            if (stats.built_at) {
                var date = new Date(stats.built_at);
                if (!isNaN(date.getTime())) {
                    els.builtAt.textContent = "Indexed " + date.toLocaleDateString("en-GB", {
                        day: "2-digit", month: "short"
                    });
                }
            }
            Array.prototype.forEach.call(document.querySelectorAll("[data-stat]"), function (el) {
                var key = el.getAttribute("data-stat");
                var value = stats[key];
                if (typeof value === "number") countUp(el, value);
            });
            populateUnions();
            populateSuggestions();
            if (state.area && !els.areaFilter.querySelector('option[value="' + cssEscape(state.area) + '"]')) {
                state.area = "";
                els.areaFilter.value = "";
            }
        }).catch(function (error) {
            els.builtAt.textContent = "Index unavailable";
            toast(error.message || "Could not load the index.");
        });
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === "function") return window.CSS.escape(value);
        return String(value).replace(/["\\]/g, "\\$&");
    }

    function populateUnions() {
        if (!state.filters) return;
        var select = els.unionFilter;
        state.filters.unions.forEach(function (union) {
            var option = document.createElement("option");
            option.value = union.key;
            option.textContent = union.name + " · " + fmt(union.records);
            select.appendChild(option);
        });
        select.value = state.union;
    }

    function areasForUnion(key) {
        if (!state.filters) return [];
        if (!key) {
            var all = [];
            state.filters.unions.forEach(function (union) {
                all = all.concat(union.areas);
            });
            return all.sort(function (a, b) {
                return a.name.localeCompare(b.name, "bn");
            });
        }
        var found = state.filters.unions.filter(function (union) {
            return union.key === key;
        })[0];
        return found ? found.areas : [];
    }

    function populateAreas() {
        var key = els.unionFilter.value;
        var areas = areasForUnion(key);
        var select = els.areaFilter;
        select.innerHTML = '<option value="">সব এলাকা</option>';
        areas.forEach(function (area) {
            var option = document.createElement("option");
            option.value = area.area_no;
            option.textContent = area.name + " · " + fmt(area.records);
            select.appendChild(option);
        });
        if (state.area && !areas.some(function (area) { return area.area_no === state.area; })) {
            state.area = "";
        }
        select.value = state.area;
    }

    function populateSuggestions() {
        var areas = areasForUnion("").slice(0, 5);
        els.suggestAreas.innerHTML = "";
        areas.forEach(function (area) {
            var button = document.createElement("button");
            button.type = "button";
            button.textContent = area.name;
            button.addEventListener("click", function () {
                state.area = area.area_no;
                state.union = "";
                syncControls();
                state.page = 1;
                runSearch(false, true);
            });
            els.suggestAreas.appendChild(button);
        });
    }

    /* ---------- search ---------- */

    function currentQuery() {
        return els.q.value.trim();
    }

    function hasFilters() {
        if (state.union || state.area) return true;
        return countAdv(state.adv) > 0;
    }

    function skeleton() {
        var html = "";
        for (var i = 0; i < 6; i++) {
            html += '<div class="skeleton-card"><span class="sk sk-avatar"></span><span>' +
                '<span class="sk sk-line w60"></span><span class="sk sk-line w40"></span></span></div>';
        }
        els.results.innerHTML = html;
        els.loadMore.hidden = true;
        els.emptyState.classList.add("hidden");
        els.resultsTitle.textContent = "Searching the index…";
        els.resultCount.textContent = "—";
        els.resultTime.textContent = "";
    }

    function searchHint(message) {
        els.results.innerHTML = '<div class="no-results"><strong>Keep typing</strong><span>' +
            esc(message) + "</span></div>";
        els.loadMore.hidden = true;
        els.emptyState.classList.add("hidden");
        els.resultCount.textContent = "0";
        els.resultTime.textContent = "";
        els.resultsTitle.textContent = "Waiting for a better query";
        hideWorkspace();
    }

    function runSearch(append, fromSubmit) {
        var q = currentQuery();
        state.q = q;
        state.union = els.unionFilter.value;
        state.area = els.areaFilter.value;
        state.adv = advFromInputs();
        updateClearButton();

        var chars = Array.from(q).length;
        if (!append) {
            state.page = 1;
        }
        if (!q && !hasFilters()) {
            resetResultsView();
            writeUrl();
            return;
        }
        if (q && chars < 2 && !hasFilters()) {
            searchHint("কমপক্ষে ২টি অক্ষর লিখুন, অথবা একটি ইউনিয়ন বা এলাকা বাছুন।");
            return;
        }

        if (state.searchAbort) state.searchAbort.abort();
        state.searchAbort = new AbortController();
        var seq = ++state.searchSeq;
        var pageToLoad = append ? state.page + 1 : 1;
        els.searchForm.classList.add("is-loading");
        showWorkspace();
        if (!append) skeleton();

        var params = {
            q: q,
            union: state.union,
            area: state.area,
            page: pageToLoad,
            limit: state.limit
        };
        ADV_FIELDS.forEach(function (pair) {
            params[pair[0]] = state.adv[pair[0]];
        });
        var url = apiUrl("search", params);

        fetchJson(url, state.searchAbort.signal).then(function (data) {
            if (seq !== state.searchSeq) return;
            state.page = data.page;
            state.total = data.total;
            state.capped = !!data.capped;
            if (append) {
                state.items = state.items.concat(data.items);
            } else {
                state.items = data.items;
            }
            renderResults();
            writeUrl();
            if (fromSubmit && data.items.length && !state.selectedId) {
                selectVoter(data.items[0].id, false);
            }
        }).catch(function (error) {
            if (error.name === "AbortError") return;
            if (seq !== state.searchSeq) return;
            els.results.innerHTML = '<div class="no-results"><strong>Search failed</strong><span>' +
                esc(error.message || "Try again in a moment.") + "</span></div>";
            els.emptyState.classList.add("hidden");
            els.loadMore.hidden = true;
            els.resultCount.textContent = "—";
            els.resultTime.textContent = "";
            els.resultsTitle.textContent = "Something went wrong";
            toast(error.message || "Search failed.");
        }).finally(function () {
            if (seq === state.searchSeq) {
                els.searchForm.classList.remove("is-loading");
            }
        });
    }

    function resetResultsView() {
        state.items = [];
        state.total = 0;
        state.capped = false;
        state.page = 1;
        els.results.innerHTML = "";
        els.emptyState.classList.remove("hidden");
        els.loadMore.hidden = true;
        els.resultsTitle.textContent = "Ready when you are";
        els.resultCount.textContent = "—";
        els.resultTime.textContent = "";
        hideWorkspace();
    }

    function showWorkspace() {
        if (!els.workspace || !els.workspace.hidden) return;
        els.workspace.hidden = false;
    }

    function hideWorkspace() {
        if (!els.workspace) return;
        els.workspace.hidden = true;
    }

    function highlightTokens() {
        return currentQuery().split(/\s+/).filter(function (token) {
            return Array.from(token).length >= 2;
        });
    }

    function markText(text, tokens) {
        var value = String(text == null ? "" : text);
        if (!tokens.length) return esc(value);
        var pattern;
        try {
            pattern = new RegExp("(" + tokens.map(function (token) {
                return token.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
            }).join("|") + ")", "giu");
        } catch (error) {
            return esc(value);
        }
        return value.split(pattern).map(function (part, index) {
            return index % 2 === 1 ? "<mark>" + esc(part) + "</mark>" : esc(part);
        }).join("");
    }

    function initialOf(name) {
        var chars = Array.from(String(name || "?").replace(/\s+/g, ""));
        return chars.length ? chars[0] : "?";
    }

    function cardHtml(item, tokens) {
        var cancelled = item.status !== "ok";
        return '<button type="button" class="result-card" data-id="' + item.id + '"' +
            (state.selectedId === item.id ? ' aria-pressed="true"' : "") + '>' +
            '<span class="avatar" aria-hidden="true">' + esc(initialOf(item.name)) + "</span>" +
            '<span class="rc-body">' +
            '<span class="rc-name">' + markText(item.name || "নাম নেই", tokens) + "</span>" +
            '<span class="rc-sub">' +
            '<span class="vno">' + esc(item.voter_no_digits || item.voter_no || "—") + "</span>" +
            '<span class="mini-dot"></span>' +
            '<span class="trunc">' + esc(item.area || "—") + "</span>" +
            '<span class="mini-dot union-name"></span>' +
            '<span class="trunc union-name">' + esc(item.union || "") + "</span>" +
            "</span></span>" +
            '<span class="rc-right">' +
            '<span class="status-tag ' + (cancelled ? "no" : "ok") + '">' +
            (cancelled ? "বাতিল" : "সক্রিয়") + "</span>" +
            '<svg class="rc-arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true">' +
            '<path d="m8 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            "</span></button>";
    }

    function renderResults() {
        var tokens = highlightTokens();
        if (!state.items.length) {
            els.results.innerHTML = '<div class="no-results"><strong>No voters found</strong>' +
                "<span>বানান বদলে দেখুন, অথবা ফিল্টার কমিয়ে আবার চেষ্টা করুন।</span></div>";
            els.emptyState.classList.add("hidden");
            els.loadMore.hidden = true;
        } else {
            els.results.innerHTML = state.items.map(function (item) {
                return cardHtml(item, tokens);
            }).join("");
            els.emptyState.classList.add("hidden");
            els.loadMore.hidden = !(state.total > state.items.length);
        }
        var shown = state.capped && state.total >= 2000 ? fmt(state.total) + "+" : fmt(state.total);
        els.resultCount.textContent = state.total === 1 ? "1 match" : shown + " matches";
        els.resultTime.textContent = state.q || hasFilters() ? "indexed search" : "";
        if (state.q) {
            els.resultsTitle.textContent = 'Results for "' + state.q + '"';
        } else if (state.area) {
            els.resultsTitle.textContent = "Voters in this area";
        } else if (state.union) {
            els.resultsTitle.textContent = "Voters in this union";
        } else {
            els.resultsTitle.textContent = "Filtered voters";
        }
        var active = els.results.querySelector('.result-card[data-id="' + state.selectedId + '"]');
        if (active) active.classList.add("is-active");
    }

    function markSelected(id) {
        Array.prototype.forEach.call(els.results.querySelectorAll(".result-card"), function (card) {
            var isActive = card.getAttribute("data-id") === String(id);
            card.classList.toggle("is-active", isActive);
            if (isActive) {
                card.setAttribute("aria-pressed", "true");
            } else {
                card.removeAttribute("aria-pressed");
            }
        });
    }

    /* ---------- voter detail ---------- */

    function selectVoter(id, openDrawer) {
        if (state.voterAbort) state.voterAbort.abort();
        state.voterAbort = new AbortController();
        state.selectedId = id;
        markSelected(id);
        writeUrl();
        var cached = state.items.filter(function (item) { return item.id === id; })[0];
        if (cached) {
            els.viewerBody.hidden = false;
            els.viewerEmpty.hidden = true;
            els.vAvatar.textContent = initialOf(cached.name);
            els.vName.textContent = cached.name || "—";
            els.vStatus.textContent = cached.status === "ok" ? "Active" : "Cancelled";
            els.vStatus.className = "badge " + (cached.status === "ok" ? "badge-ok" : "badge-no");
            els.vMeta.textContent = [cached.area, cached.union, "পৃষ্ঠা " + cached.page].filter(Boolean).join(" · ");
        }
        if (openDrawer !== false) openViewer();

        fetchJson(apiUrl("voter", { id: id }), state.voterAbort.signal).then(function (data) {
            if (state.selectedId !== id) return;
            state.selected = data;
            renderVoter(data);
            if (state.live) loadPdf(data);
            else showPdfOff();
        }).catch(function (error) {
            if (error.name === "AbortError") return;
            toast(error.message || "Could not load that voter.");
        });
    }

    function renderVoter(data) {
        var voter = data.voter;
        els.viewerBody.hidden = false;
        els.viewerEmpty.hidden = true;
        els.vAvatar.textContent = initialOf(voter.name);
        els.vName.textContent = voter.name || "নাম নেই";
        els.vStatus.textContent = voter.status === "ok" ? "Active" : "Cancelled";
        els.vStatus.className = "badge " + (voter.status === "ok" ? "badge-ok" : "badge-no");
        els.vMeta.textContent = [
            voter.area,
            voter.union,
            voter.gender === "female" ? "মহিলা তালিকা" : voter.gender === "male" ? "পুরুষ তালিকা" : ""
        ].filter(Boolean).join(" · ");
        els.dVoterNo.textContent = voter.voter_no || voter.voter_no_digits || "—";
        els.dSerial.textContent = voter.serial_bn ? voter.serial_bn + " (" + voter.serial + ")" : String(voter.serial || "—");
        els.dFather.textContent = voter.father || "—";
        els.dMother.textContent = voter.mother || "—";
        els.dOccupation.textContent = voter.occupation || "—";
        els.dDob.textContent = voter.dob || "—";
        els.dAddress.textContent = voter.address || "—";
        els.dChips.innerHTML = [
            chip(voter.area, "এলাকা"),
            chip(voter.union, "ইউনিয়ন"),
            chip(voter.gender === "female" ? "মহিলা" : voter.gender === "male" ? "পুরুষ" : "—", "লিঙ্গ"),
            chip("পৃষ্ঠা " + voter.page + " · কলাম " + voter.column, "")
        ].join("");
        els.pdfFileName.textContent = (voter.area || "Voter list") + " · " +
            (data.pdf.pages || "?") + " pages · ছবি ছাড়া";
        els.pageTotal.textContent = String(data.pdf.pages || "?");
        els.pageNow.textContent = String(voter.page);
        state.pdf = {
            token: data.pdf.token,
            pages: data.pdf.pages,
            voterPage: voter.page,
            currentPage: voter.page,
            rect: data.rect,
            zoom: 1,
            fitScale: 1,
            doc: null
        };
        els.pdfCanvasWrap.hidden = true;
        els.pdfToolbar.hidden = true;
        els.pdfError.hidden = true;
        els.pdfLoading.hidden = true;
        if (state.live) {
            els.pdfOff.hidden = true;
        } else {
            showPdfOff();
        }
    }

    function chip(value, label) {
        if (!value) return "";
        return '<span class="chip">' + (label ? "<b>" + esc(label) + "</b>" : "") +
            (label ? '<span class="cdot"></span>' : "") + esc(value) + "</span>";
    }

    function copyDetails() {
        if (!state.selected) return;
        var voter = state.selected.voter;
        var lines = [
            "নাম: " + (voter.name || ""),
            "ভোটার নম্বর: " + (voter.voter_no || voter.voter_no_digits || ""),
            "ক্রমিক: " + (voter.serial || ""),
            "পিতা: " + (voter.father || ""),
            "মাতা: " + (voter.mother || ""),
            "পেশা: " + (voter.occupation || ""),
            "জন্ম তারিখ: " + (voter.dob || ""),
            "ঠিকানা: " + (voter.address || ""),
            "এলাকা: " + (voter.area || "") + " (" + (voter.area_no || "") + ")",
            "ইউনিয়ন: " + (voter.union || ""),
            "পৃষ্ঠা: " + voter.page + " · কলাম: " + voter.column
        ];
        var text = lines.join("\n");
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                toast("Voter details copied.");
            }).catch(function () {
                toast("Copy failed.");
            });
        } else {
            var area = document.createElement("textarea");
            area.value = text;
            document.body.appendChild(area);
            area.select();
            try {
                document.execCommand("copy");
                toast("Voter details copied.");
            } catch (error) {
                toast("Copy failed.");
            }
            document.body.removeChild(area);
        }
    }

    function openViewer() {
        els.viewerPanel.classList.add("is-open");
        document.body.classList.add("viewer-open");
        els.backdrop.hidden = false;
        requestAnimationFrame(function () {
            els.backdrop.classList.add("show");
        });
        if (window.matchMedia("(max-width: 1020px)").matches) {
            els.pdfStage.scrollTop = 0;
        }
    }

    function closeViewer() {
        els.viewerPanel.classList.remove("is-open");
        document.body.classList.remove("viewer-open");
        els.backdrop.classList.remove("show");
        setTimeout(function () {
            els.backdrop.hidden = true;
        }, 240);
    }

    /* ---------- live PDF ---------- */

    function showPdfOff() {
        els.pdfOff.hidden = false;
        els.pdfToolbar.hidden = true;
        els.pdfLoading.hidden = true;
        els.pdfError.hidden = true;
        els.pdfCanvasWrap.hidden = true;
    }

    function showPdfLoading() {
        els.pdfOff.hidden = true;
        els.pdfError.hidden = true;
        els.pdfLoading.hidden = false;
        els.pdfToolbar.hidden = true;
        els.pdfCanvasWrap.hidden = true;
    }

    function showPdfError(message) {
        els.pdfOff.hidden = true;
        els.pdfLoading.hidden = true;
        els.pdfToolbar.hidden = true;
        els.pdfCanvasWrap.hidden = true;
        els.pdfError.hidden = false;
        if (message) {
            els.pdfError.querySelector("p").textContent = message;
        }
    }

    function destroyPdf() {
        if (state.loadingTask) {
            try { state.loadingTask.destroy(); } catch (error) { /* noop */ }
            state.loadingTask = null;
        }
        if (state.currentDoc) {
            try { state.currentDoc.destroy(); } catch (error) { /* noop */ }
            state.currentDoc = null;
        }
    }

    function loadPdf(data) {
        var seq = ++state.pdfLoadSeq;
        showPdfLoading();
        destroyPdf();
        els.pdfLoading.hidden = false;
        els.pageNow.textContent = String(data.voter.page);
        els.pageTotal.textContent = String(data.pdf.pages || "?");

        var url = apiUrl("pdf", { t: data.pdf.token });
        ensurePdfJs().then(function (lib) {
            if (seq !== state.pdfLoadSeq) return;
            var task = lib.getDocument({
                url: url,
                cMapUrl: "assets/pdfjs/cmaps/",
                cMapPacked: true,
                standardFontDataUrl: "assets/pdfjs/standard_fonts/"
            });
            state.loadingTask = task;
            return task.promise.then(function (doc) {
                if (seq !== state.pdfLoadSeq) {
                    try { doc.destroy(); } catch (error) { /* noop */ }
                    return;
                }
                state.currentDoc = doc;
                state.pdf.doc = doc;
                state.pdf.zoom = 1;
                els.pdfLoading.hidden = true;
                els.pdfToolbar.hidden = false;
                els.pdfCanvasWrap.hidden = false;
                renderPdfPage(data.voter.page, true);
            });
        }).catch(function (error) {
            if (seq !== state.pdfLoadSeq) return;
            showPdfError("The source PDF could not be loaded.");
            toast(error && error.message ? error.message : "PDF load failed.");
        });
    }

    function fitScaleFor(page) {
        var base = page.getViewport({ scale: 1 });
        var width = (els.pdfStage.clientWidth || 0) - 36;
        if (width <= 0) return 1;
        return Math.max(0.35, Math.min(2.2, width / base.width));
    }

    function renderPdfPage(pageNumber, autoScroll) {
        if (!state.currentDoc || !state.pdf) return;
        var doc = state.currentDoc;
        var seq = ++state.pdfRenderSeq;
        var target = Math.max(1, Math.min(state.pdf.pages || doc.numPages, pageNumber));
        state.pdf.currentPage = target;
        els.pageNow.textContent = String(target);
        els.pagePrev.disabled = target <= 1;
        els.pageNext.disabled = target >= doc.numPages;
        els.jumpVoter.hidden = target === state.pdf.voterPage;

        doc.getPage(target).then(function (page) {
            if (seq !== state.pdfRenderSeq) return;
            var fit = fitScaleFor(page);
            state.pdf.fitScale = fit;
            var scale = fit * (state.pdf.zoom || 1);
            var viewport = page.getViewport({ scale: scale });
            var dpr = Math.min(2, window.devicePixelRatio || 1);
            var canvas = els.pdfCanvas;
            var context = canvas.getContext("2d", { alpha: false });
            canvas.width = Math.floor(viewport.width * dpr);
            canvas.height = Math.floor(viewport.height * dpr);
            canvas.style.width = Math.round(viewport.width) + "px";
            canvas.style.height = Math.round(viewport.height) + "px";
            els.pdfCanvasWrap.style.width = Math.round(viewport.width) + "px";
            els.pdfLoading.hidden = false;
            var renderTask = page.render({
                canvasContext: context,
                viewport: viewport,
                transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null
            });
            return renderTask.promise.then(function () {
                if (seq !== state.pdfRenderSeq) return;
                els.pdfLoading.hidden = true;
                els.pdfCanvasWrap.hidden = false;
                positionHighlight(viewport);
                if (autoScroll) scrollToHighlight();
            });
        }).catch(function () {
            if (seq !== state.pdfRenderSeq) return;
            showPdfError("That page could not be rendered.");
        });
    }

    function positionHighlight(viewport) {
        var highlight = els.pdfHighlight;
        var pdf = state.pdf;
        if (!pdf || !pdf.rect || pdf.currentPage !== pdf.voterPage) {
            highlight.style.display = "none";
            return;
        }
        var rect = pdf.rect;
        var converted = viewport.convertToViewportRectangle([rect.x0, rect.y0, rect.x1, rect.y1]);
        var left = Math.min(converted[0], converted[2]);
        var top = Math.min(converted[1], converted[3]);
        var width = Math.abs(converted[2] - converted[0]);
        var height = Math.abs(converted[3] - converted[1]);
        if (!isFinite(left) || !isFinite(top) || width <= 0 || height <= 0) {
            highlight.style.display = "none";
            return;
        }
        highlight.style.display = "block";
        highlight.style.left = left + "px";
        highlight.style.top = top + "px";
        highlight.style.width = width + "px";
        highlight.style.height = height + "px";
        var voter = state.selected && state.selected.voter;
        els.hlTag.textContent = voter
            ? (voter.name || "Voter") + " · " + (voter.voter_no_digits || voter.voter_no || "")
            : "Voter";
        els.hlTag.classList.toggle("below", top < 40);
    }

    function scrollToHighlight() {
        var wrap = els.pdfCanvasWrap;
        var highlight = els.pdfHighlight;
        if (highlight.style.display === "none") return;
        var top = highlight.offsetTop;
        var stage = els.pdfStage;
        var desired = wrap.offsetTop + top - stage.clientHeight * 0.32;
        stage.scrollTop = Math.max(0, desired);
    }

    function changeZoom(multiplier) {
        if (!state.pdf || !state.currentDoc) return;
        var next = multiplier === null ? 1 : (state.pdf.zoom || 1) * multiplier;
        state.pdf.zoom = Math.max(0.55, Math.min(3, next));
        renderPdfPage(state.pdf.currentPage, false);
    }

    /* ---------- events ---------- */

    function bindEvents() {
        els.searchForm.addEventListener("submit", function (event) {
            event.preventDefault();
            runSearch(false, true);
        });

        els.q.addEventListener("input", updateClearButton);
        els.unionFilter.addEventListener("change", function () {
            state.union = els.unionFilter.value;
            state.area = "";
            populateAreas();
            updateClearButton();
        });
        els.areaFilter.addEventListener("change", function () {
            state.area = els.areaFilter.value;
            updateClearButton();
        });
        els.clearSearch.addEventListener("click", function () {
            state.union = "";
            state.area = "";
            els.q.value = "";
            state.q = "";
            state.adv = {
                name: "", father: "", mother: "", voter_no: "",
                address: "", occupation: "", serial_min: "", serial_max: "", page_no: ""
            };
            syncControls();
            resetResultsView();
            writeUrl();
            els.q.focus();
        });

        els.advToggle.addEventListener("click", function () {
            setAdvOpen(!state.advOpen);
            updateAdvBadge();
            if (state.advOpen && window.matchMedia("(max-width: 1020px)").matches) {
                setTimeout(function () {
                    var top = els.advPanel.getBoundingClientRect().top + window.scrollY - 82;
                    window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
                }, 30);
            }
        });
        ADV_FIELDS.forEach(function (pair) {
            els[pair[1]].addEventListener("input", updateAdvBadge);
            els[pair[1]].addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    runSearch(false, true);
                }
            });
        });
        els.advClear.addEventListener("click", function () {
            state.adv = {
                name: "", father: "", mother: "", voter_no: "",
                address: "", occupation: "", serial_min: "", serial_max: "", page_no: ""
            };
            syncAdvInputs();
            els.advName.focus();
        });

        els.loadMore.addEventListener("click", function () {
            els.loadMore.disabled = true;
            els.loadMore.querySelector("span").textContent = "Loading more…";
            runSearch(true, false);
            setTimeout(function () {
                els.loadMore.disabled = false;
                els.loadMore.querySelector("span").textContent = "Load more results";
            }, 800);
        });

        els.results.addEventListener("click", function (event) {
            var card = event.target.closest(".result-card");
            if (!card) return;
            var id = parseInt(card.getAttribute("data-id"), 10);
            if (isFinite(id)) selectVoter(id, true);
        });

        els.copyBtn.addEventListener("click", copyDetails);
        els.viewerClose.addEventListener("click", closeViewer);
        els.backdrop.addEventListener("click", closeViewer);

        els.liveToggle.addEventListener("change", function () {
            state.live = els.liveToggle.checked;
            if (!state.live) {
                showPdfOff();
                return;
            }
            if (state.selected) {
                if (!state.currentDoc || (state.pdf && state.pdf.token !== state.selected.pdf.token)) {
                    loadPdf(state.selected);
                } else {
                    els.pdfOff.hidden = true;
                    els.pdfToolbar.hidden = false;
                    els.pdfCanvasWrap.hidden = false;
                    renderPdfPage(state.pdf.voterPage, true);
                }
            }
        });

        els.enableLive.addEventListener("click", function () {
            state.live = true;
            els.liveToggle.checked = true;
            if (state.selected) loadPdf(state.selected);
        });

        els.pagePrev.addEventListener("click", function () {
            if (state.pdf) renderPdfPage(state.pdf.currentPage - 1, false);
        });
        els.pageNext.addEventListener("click", function () {
            if (state.pdf) renderPdfPage(state.pdf.currentPage + 1, false);
        });
        els.jumpVoter.addEventListener("click", function () {
            if (state.pdf) renderPdfPage(state.pdf.voterPage, true);
        });
        els.zoomIn.addEventListener("click", function () { changeZoom(1.2); });
        els.zoomOut.addEventListener("click", function () { changeZoom(1 / 1.2); });
        els.zoomFit.addEventListener("click", function () { changeZoom(null); });
        els.retryPdf.addEventListener("click", function () {
            if (state.selected) loadPdf(state.selected);
        });

        document.addEventListener("keydown", function (event) {
            var active = document.activeElement;
            var typing = active && (active.tagName === "INPUT" || active.tagName === "SELECT" || active.tagName === "TEXTAREA");
            if (event.key === "/" && !typing) {
                event.preventDefault();
                els.q.focus();
                els.q.select();
            } else if (event.key === "Escape") {
                if (els.viewerPanel.classList.contains("is-open")) closeViewer();
                else if (typing) active.blur();
            }
        });

        window.addEventListener("resize", debounce(function () {
            if (state.currentDoc && !
                els.pdfCanvasWrap.hidden && state.pdf) {
                renderPdfPage(state.pdf.currentPage, false);
            }
        }, 220));
    }

    function init() {
        cacheElements();
        var initialId = readUrl();
        syncControls();
        bindEvents();
        loadMeta();
        if (state.q || hasFilters()) {
            runSearch(false, false);
        }
        if (initialId) {
            showWorkspace();
            selectVoter(initialId, false);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
