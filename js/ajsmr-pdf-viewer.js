/**
 * AJSMR Professional Academic PDF Viewer Component
 * Reusable PDF.js modal viewer for currentissue.php and issuelist.php
 */

(function () {
  'use strict';

  var PDF_JS_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
  var PDF_WORKER_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

  var pdfDoc = null;
  var currentUrl = '';
  var currentTitle = '';
  var currentScale = 1.0;
  var currentPageNum = 1;
  var totalPages = 0;
  var isRendering = false;
  var renderTasks = [];

  var overlayEl = null;
  var modalEl = null;
  var bodyEl = null;
  var pagesEl = null;
  var loaderEl = null;
  var errorEl = null;
  var titleEl = null;
  var pageIndicatorEl = null;
  var zoomLabelEl = null;
  var downloadBtnHeader = null;
  var downloadBtnToolbar = null;
  var fullscreenBtnHeader = null;
  var fullscreenBtnToolbar = null;

  function loadPdfJs(callback) {
    if (window.pdfjsLib) {
      if (callback) callback();
      return;
    }
    var script = document.createElement('script');
    script.src = PDF_JS_CDN;
    script.onload = function () {
      if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDF_WORKER_CDN;
      }
      if (callback) callback();
    };
    script.onerror = function () {
      console.warn('PDF.js CDN failed to load. Will attempt fallback.');
      if (callback) callback(new Error('Failed to load PDF.js script'));
    };
    document.head.appendChild(script);
  }

  function ensureModalDom() {
    if (overlayEl) return;

    overlayEl = document.createElement('div');
    overlayEl.className = 'ajsmr-pdf-overlay';
    overlayEl.setAttribute('aria-hidden', 'true');
    overlayEl.setAttribute('role', 'dialog');
    overlayEl.setAttribute('aria-label', 'AJSMR PDF Viewer Modal');

    overlayEl.innerHTML = [
      '<div class="ajsmr-pdf-modal" id="ajsmrPdfModal">',
      '  <div class="ajsmr-pdf-header">',
      '    <div class="ajsmr-pdf-header-title-box">',
      '      <span class="ajsmr-pdf-badge">AJSMR PDF Viewer</span>',
      '      <span class="ajsmr-pdf-doc-title" id="ajsmrPdfTitle">Research Paper</span>',
      '    </div>',
      '    <div class="ajsmr-pdf-header-actions">',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfHeaderFullscreen" title="Toggle Fullscreen">⛶ Fullscreen</button>',
      '      <a href="#" class="ajsmr-pdf-btn ajsmr-pdf-btn-primary" id="ajsmrPdfHeaderDownload" download title="Download Paper PDF">⬇ Download</a>',
      '      <button type="button" class="ajsmr-pdf-btn ajsmr-pdf-btn-close" id="ajsmrPdfHeaderClose" title="Close Viewer (Esc)">✕</button>',
      '    </div>',
      '  </div>',
      '  <div class="ajsmr-pdf-body" id="ajsmrPdfBody">',
      '    <div class="ajsmr-pdf-loader" id="ajsmrPdfLoader">',
      '      <div class="ajsmr-pdf-spinner"></div>',
      '      <div class="ajsmr-pdf-loader-text">Loading manuscript...</div>',
      '    </div>',
      '    <div class="ajsmr-pdf-error" id="ajsmrPdfError" style="display:none;">',
      '      <div class="ajsmr-pdf-error-icon">⚠️</div>',
      '      <div class="ajsmr-pdf-error-title">Unable to load this paper</div>',
      '      <div class="ajsmr-pdf-error-desc">Please try again or download the PDF directly.</div>',
      '      <div class="ajsmr-pdf-error-actions">',
      '        <button type="button" class="ajsmr-pdf-btn ajsmr-pdf-btn-primary" id="ajsmrPdfRetryBtn">↻ Retry</button>',
      '        <a href="#" class="ajsmr-pdf-btn" id="ajsmrPdfErrorDownload" download>⬇ Download PDF</a>',
      '      </div>',
      '    </div>',
      '    <div class="ajsmr-pdf-pages" id="ajsmrPdfPages"></div>',
      '  </div>',
      '  <div class="ajsmr-pdf-toolbar">',
      '    <div class="ajsmr-pdf-toolbar-group">',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfPrev" title="Previous Page">▲ Prev</button>',
      '      <span class="ajsmr-pdf-page-indicator" id="ajsmrPdfPageIndicator">Page 0 / 0</span>',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfNext" title="Next Page">▼ Next</button>',
      '    </div>',
      '    <div class="ajsmr-pdf-toolbar-group">',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfZoomOut" title="Zoom Out">−</button>',
      '      <span class="ajsmr-pdf-zoom-label" id="ajsmrPdfZoomLabel">100%</span>',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfZoomIn" title="Zoom In">+</button>',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfFitWidth" title="Fit to Container Width">Fit Width</button>',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfFitPage" title="Fit Page Height">Fit Page</button>',
      '    </div>',
      '    <div class="ajsmr-pdf-toolbar-group">',
      '      <button type="button" class="ajsmr-pdf-btn" id="ajsmrPdfToolbarFullscreen" title="Toggle Fullscreen">⛶</button>',
      '      <a href="#" class="ajsmr-pdf-btn ajsmr-pdf-btn-primary" id="ajsmrPdfToolbarDownload" download>⬇ Download</a>',
      '      <button type="button" class="ajsmr-pdf-btn ajsmr-pdf-btn-close" id="ajsmrPdfToolbarClose">✕ Close</button>',
      '    </div>',
      '  </div>',
      '</div>'
    ].join('\n');

    document.body.appendChild(overlayEl);

    modalEl = document.getElementById('ajsmrPdfModal');
    bodyEl = document.getElementById('ajsmrPdfBody');
    pagesEl = document.getElementById('ajsmrPdfPages');
    loaderEl = document.getElementById('ajsmrPdfLoader');
    errorEl = document.getElementById('ajsmrPdfError');
    titleEl = document.getElementById('ajsmrPdfTitle');
    pageIndicatorEl = document.getElementById('ajsmrPdfPageIndicator');
    zoomLabelEl = document.getElementById('ajsmrPdfZoomLabel');

    downloadBtnHeader = document.getElementById('ajsmrPdfHeaderDownload');
    downloadBtnToolbar = document.getElementById('ajsmrPdfToolbarDownload');
    var errorDownloadBtn = document.getElementById('ajsmrPdfErrorDownload');

    fullscreenBtnHeader = document.getElementById('ajsmrPdfHeaderFullscreen');
    fullscreenBtnToolbar = document.getElementById('ajsmrPdfToolbarFullscreen');

    document.getElementById('ajsmrPdfHeaderClose').addEventListener('click', closeViewer);
    document.getElementById('ajsmrPdfToolbarClose').addEventListener('click', closeViewer);

    overlayEl.addEventListener('click', function (e) {
      if (e.target === overlayEl) {
        closeViewer();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (overlayEl.classList.contains('active')) {
        if (e.key === 'Escape') {
          closeViewer();
        }
      }
    });

    fullscreenBtnHeader.addEventListener('click', toggleFullscreen);
    fullscreenBtnToolbar.addEventListener('click', toggleFullscreen);

    document.getElementById('ajsmrPdfZoomIn').addEventListener('click', function () {
      updateScale(currentScale + 0.15);
    });
    document.getElementById('ajsmrPdfZoomOut').addEventListener('click', function () {
      updateScale(currentScale - 0.15);
    });
    document.getElementById('ajsmrPdfFitWidth').addEventListener('click', function () {
      fitToWidth();
    });
    document.getElementById('ajsmrPdfFitPage').addEventListener('click', function () {
      fitToPage();
    });

    document.getElementById('ajsmrPdfPrev').addEventListener('click', function () {
      scrollToPage(currentPageNum - 1);
    });
    document.getElementById('ajsmrPdfNext').addEventListener('click', function () {
      scrollToPage(currentPageNum + 1);
    });

    document.getElementById('ajsmrPdfRetryBtn').addEventListener('click', function () {
      loadDocument();
    });

    bodyEl.addEventListener('scroll', handleScroll, { passive: true });
  }

  function toggleFullscreen() {
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
      if (modalEl.requestFullscreen) {
        modalEl.requestFullscreen();
      } else if (modalEl.webkitRequestFullscreen) {
        modalEl.webkitRequestFullscreen();
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
    }
  }

  function setDownloadUrls(url) {
    if (downloadBtnHeader) downloadBtnHeader.href = url;
    if (downloadBtnToolbar) downloadBtnToolbar.href = url;
    var errorDownload = document.getElementById('ajsmrPdfErrorDownload');
    if (errorDownload) errorDownload.href = url;
  }

  function showLoader() {
    loaderEl.style.display = 'flex';
    errorEl.style.display = 'none';
    pagesEl.style.display = 'none';
  }

  function hideLoader() {
    loaderEl.style.display = 'none';
    pagesEl.style.display = 'flex';
  }

  function showError() {
    loaderEl.style.display = 'none';
    pagesEl.style.display = 'none';
    errorEl.style.display = 'flex';
  }

  function cancelRenderTasks() {
    for (var i = 0; i < renderTasks.length; i++) {
      if (renderTasks[i] && renderTasks[i].cancel) {
        try { renderTasks[i].cancel(); } catch (e) {}
      }
    }
    renderTasks = [];
  }

  function updateScale(newScale) {
    newScale = Math.max(0.4, Math.min(3.0, newScale));
    currentScale = newScale;
    zoomLabelEl.textContent = Math.round(currentScale * 100) + '%';
    if (pdfDoc) {
      renderAllPages();
    }
  }

  function fitToWidth() {
    if (!pdfDoc) return;
    pdfDoc.getPage(1).then(function (page) {
      var viewport = page.getViewport({ scale: 1.0 });
      var availWidth = bodyEl.clientWidth - 48; // padding
      if (availWidth > 100 && viewport.width > 0) {
        updateScale(availWidth / viewport.width);
      }
    });
  }

  function fitToPage() {
    if (!pdfDoc) return;
    pdfDoc.getPage(1).then(function (page) {
      var viewport = page.getViewport({ scale: 1.0 });
      var availHeight = bodyEl.clientHeight - 48;
      if (availHeight > 100 && viewport.height > 0) {
        updateScale(availHeight / viewport.height);
      }
    });
  }

  function scrollToPage(pageNum) {
    if (pageNum < 1 || pageNum > totalPages) return;
    var pageCard = document.getElementById('ajsmr-pdf-page-' + pageNum);
    if (pageCard) {
      pageCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function handleScroll() {
    if (!pagesEl || totalPages === 0) return;
    var cards = pagesEl.querySelectorAll('.ajsmr-pdf-page-card');
    var bodyTop = bodyEl.getBoundingClientRect().top;
    var bestPage = 1;
    var minDiff = Infinity;

    for (var i = 0; i < cards.length; i++) {
      var rect = cards[i].getBoundingClientRect();
      var diff = Math.abs(rect.top - bodyTop);
      if (diff < minDiff) {
        minDiff = diff;
        bestPage = i + 1;
      }
    }
    currentPageNum = bestPage;
    updatePageIndicator();
  }

  function updatePageIndicator() {
    if (pageIndicatorEl) {
      pageIndicatorEl.textContent = 'Page ' + currentPageNum + ' / ' + totalPages;
    }
  }

  function renderAllPages() {
    cancelRenderTasks();
    pagesEl.innerHTML = '';

    var renderPromise = Promise.resolve();

    for (var i = 1; i <= totalPages; i++) {
      (function (pageNum) {
        renderPromise = renderPromise.then(function () {
          return renderPage(pageNum);
        });
      })(i);
    }
  }

  function renderPage(pageNum) {
    return pdfDoc.getPage(pageNum).then(function (page) {
      var card = document.createElement('div');
      card.className = 'ajsmr-pdf-page-card';
      card.id = 'ajsmr-pdf-page-' + pageNum;

      var canvas = document.createElement('canvas');
      var ctx = canvas.getContext('2d');

      var dpr = window.devicePixelRatio || 1;
      var viewport = page.getViewport({ scale: currentScale });

      canvas.width = Math.floor(viewport.width * dpr);
      canvas.height = Math.floor(viewport.height * dpr);
      canvas.style.width = Math.floor(viewport.width) + 'px';
      canvas.style.height = Math.floor(viewport.height) + 'px';

      ctx.scale(dpr, dpr);

      var pageBadge = document.createElement('div');
      pageBadge.className = 'ajsmr-pdf-page-badge';
      pageBadge.textContent = pageNum + ' / ' + totalPages;

      card.appendChild(canvas);
      card.appendChild(pageBadge);
      pagesEl.appendChild(card);

      var renderContext = {
        canvasContext: ctx,
        viewport: viewport
      };

      var task = page.render(renderContext);
      renderTasks.push(task);
      return task.promise.catch(function (err) {
        if (err.name !== 'RenderingCancelledException') {
          console.error('Page render error:', err);
        }
      });
    });
  }

  function loadDocument() {
    showLoader();
    cancelRenderTasks();

    loadPdfJs(function (err) {
      if (err || !window.pdfjsLib) {
        showFallbackEmbed();
        return;
      }

      var loadingTask = window.pdfjsLib.getDocument({
        url: currentUrl,
        cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
        cMapPacked: true
      });

      loadingTask.promise.then(function (pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        currentPageNum = 1;
        updatePageIndicator();
        hideLoader();

        // Compute initial comfortable fit width
        pdfDoc.getPage(1).then(function (page) {
          var viewport = page.getViewport({ scale: 1.0 });
          var availWidth = bodyEl.clientWidth - 48;
          if (availWidth > 300 && viewport.width > 0) {
            currentScale = Math.min(1.8, Math.max(0.7, availWidth / viewport.width));
          } else {
            currentScale = 1.0;
          }
          zoomLabelEl.textContent = Math.round(currentScale * 100) + '%';
          renderAllPages();
        });
      }).catch(function (error) {
        console.error('Error loading PDF via PDF.js:', error);
        showFallbackEmbed();
      });
    });
  }

  function showFallbackEmbed() {
    // If canvas PDF rendering fails, show error state with retry and download options
    showError();
  }

  function openViewer(url, title) {
    if (!url) return;
    ensureModalDom();

    currentUrl = url;
    currentTitle = title || 'AJSMR Published Manuscript';

    if (titleEl) titleEl.textContent = currentTitle;
    setDownloadUrls(currentUrl);

    document.body.style.overflow = 'hidden';
    overlayEl.classList.add('active');
    overlayEl.setAttribute('aria-hidden', 'false');

    loadDocument();
  }

  function closeViewer() {
    if (!overlayEl) return;
    cancelRenderTasks();
    if (pdfDoc && pdfDoc.destroy) {
      try { pdfDoc.destroy(); } catch (e) {}
    }
    pdfDoc = null;
    currentUrl = '';

    overlayEl.classList.remove('active');
    overlayEl.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    if (document.fullscreenElement || document.webkitFullscreenElement) {
      try {
        if (document.exitFullscreen) document.exitFullscreen();
        else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
      } catch (e) {}
    }
  }

  // Global handler for click events on elements or programmatic calls
  window.openAjsmrPdfViewer = function (event, elementOrUrl, title) {
    if (event && event.preventDefault) {
      event.preventDefault();
    }
    var url = '';
    var paperTitle = title || '';

    if (typeof elementOrUrl === 'string') {
      url = elementOrUrl;
    } else if (elementOrUrl && elementOrUrl.getAttribute) {
      url = elementOrUrl.getAttribute('data-ajsmr-pdf') || elementOrUrl.getAttribute('href') || '';
      paperTitle = elementOrUrl.getAttribute('data-ajsmr-title') || elementOrUrl.getAttribute('title') || title || '';
    }

    if (url) {
      openViewer(url, paperTitle);
    }
    return false;
  };

  window.AJSMRPdfViewer = {
    open: openViewer,
    close: closeViewer
  };

  // Auto-bind click handlers to any links with data-ajsmr-pdf attribute
  document.addEventListener('click', function (e) {
    var target = e.target.closest('a[data-ajsmr-pdf], a[href*="pdffiles/"]');
    if (target && target.getAttribute('data-ajsmr-pdf')) {
      e.preventDefault();
      var url = target.getAttribute('data-ajsmr-pdf') || target.getAttribute('href');
      var title = target.getAttribute('data-ajsmr-title') || target.textContent;
      openViewer(url, title);
    }
  });

})();
