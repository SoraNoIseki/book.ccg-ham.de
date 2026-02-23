document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------
    // Loading control
    // -----------------------------
    window.showLoading = function () {
        const el = document.getElementById('loading');
        if (el) el.style.display = '';
    };

    window.hideLoading = function () {
        const el = document.getElementById('loading');
        if (el) el.style.display = 'none';
    };

    // -----------------------------
    // PPT Generator validation
    // -----------------------------
    const pptGenerator = document.getElementById('pptGenerator');

    if (pptGenerator) {

        const requires = pptGenerator.querySelectorAll('textarea.required');
        const button = pptGenerator.querySelector('#downloadButton');

        function updateDownloadButton() {
            let enable = true;

            requires.forEach(function (item) {
                if (item.value.trim() === '') {
                    enable = false;
                }
            });

            if (button) {
                if (enable) {
                    button.classList.remove('opacity-50', 'cursor-not-allowed');
                    button.disabled = false;
                } else {
                    button.classList.add('opacity-50', 'cursor-not-allowed');
                    button.disabled = true;
                }
            }
        }

        updateDownloadButton();

        requires.forEach(function (item) {
            item.addEventListener('input', updateDownloadButton);
        });
    }

    // -----------------------------
    // Bible selector
    // -----------------------------
    document.querySelectorAll('.bible-selector').forEach(function (selector) {

        selector.addEventListener('change', function () {

            const value = this.value;

            if (value !== '') {
                const textarea = this.parentElement.querySelector('textarea');
                if (textarea) {
                    textarea.value = value + ':1[*]';
                }
                this.value = '';
            }
        });
    });

    // -----------------------------
    // Report list
    // -----------------------------
    const reportList = document.getElementById('reportList');

    if (reportList) {

        function sortReports() {
            const items = reportList.querySelectorAll('.report-item');

            items.forEach(function (report, index) {
                const textarea = report.querySelector('textarea');
                if (textarea) {
                    textarea.name = 'report[item' + (index + 1) + ']';
                }
            });
        }

        // Sortable
        if (typeof Sortable !== 'undefined') {
            Sortable.create(reportList, {
                onEnd: function () {
                    sortReports();
                }
            });
        }

        // Delete
        reportList.addEventListener('click', function (e) {

            if (e.target.classList.contains('delete')) {

                if (confirm('确认要删除吗？')) {

                    const reportItem = e.target.closest('.report-item');
                    if (reportItem) {
                        reportItem.remove();
                        sortReports();
                    }
                }
            }
        });

        // Add
        reportList.addEventListener('click', function (e) {

            if (e.target.classList.contains('add')) {

                const reportItem = e.target.closest('.report-item');
                if (!reportItem) return;

                const clone = reportItem.cloneNode(true);

                const textarea = clone.querySelector('textarea');
                if (textarea) {
                    textarea.value = '';
                }

                reportItem.after(clone);
                sortReports();
            }
        });
    }

    // -----------------------------
    // Song selector (AJAX load)
    // -----------------------------
    document.querySelectorAll('.song-selector[data-ajax-load]').forEach(function (selector) {

        selector.addEventListener('change', function () {

            const value = this.value;
            const targetId = this.dataset.target;
            const url = this.dataset.url;

            const target = document.getElementById(targetId);

            if (!target) return;

            if (value !== '') {

                showLoading();

                fetch(url + '?id=' + encodeURIComponent(value))
                    .then(response => response.json())
                    .then(res => {

                        hideLoading();

                        if (res.success) {
                            target.value = res.data.script_text_for_ppt_worker || '';
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error(error);
                    });

            } else {
                target.value = '';
            }
        });
    });


    // Apply song content
    document.querySelectorAll(".apply-song-content").forEach(function (button) {
        button.addEventListener("click", function () {
            const target = this.dataset.target;
            const contentElement = document.getElementById(target);
            const mainElement = document.getElementById("main-song-content");

            if (contentElement && mainElement) {
                mainElement.value = contentElement.value;
            }
        });
    });

    // Toggle song content
    document
        .querySelectorAll(".toggle-song-content")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                const target = this.dataset.target;
                const element = document.getElementById(target);

                if (element) {
                    if (
                        element.style.display === "none" ||
                        getComputedStyle(element).display === "none"
                    ) {
                        element.style.display = "";
                    } else {
                        element.style.display = "none";
                    }
                }
            });
        });

    // Beautify song content
    document
        .querySelectorAll(".beauty-song-content")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                const targetId = this.dataset.target;
                const target = document.getElementById(targetId);

                if (target) {
                    let content = target.value;
                    let lines = content.split("\n");

                    for (let i = 0; i < lines.length; i++) {
                        // delete leading and trailing spaces
                        lines[i] = lines[i].trim();

                        if (i > 1) {
                            // replace punctuation marks with full-width space
                            lines[i] = lines[i].replace(
                                /[\u3002\uff1b\uff0c\uff1a\u201c\u201d\uff08\uff09\u3001\uff1f\uff01\u0020\u002c\u002e\u003b\u003a\u003f\u0021]/g,
                                "\u3000",
                            );

                            // remove trailing full-width spaces
                            lines[i] = lines[i].replace(/\u3000+$/, "");

                            // collapse multiple full-width spaces into one
                            lines[i] = lines[i].replace(/\u3000+/g, "\u3000");
                        }
                    }

                    target.value = lines.join("\n");
                }
            });
        });

    // Get task plans (AJAX)
    document.querySelectorAll(".get-task-plans").forEach(function (button) {
        button.addEventListener("click", function () {
            const target = this.dataset.target;
            const url = this.dataset.url;
            const targetElement = document.getElementById(target);

            if (!url || !targetElement) return;

            fetch(url, {
                method: "GET",
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data && data.data && data.data.text !== undefined) {
                        if (
                            targetElement.tagName === "TEXTAREA" ||
                            targetElement.tagName === "INPUT"
                        ) {
                            targetElement.value = data.data.text;
                        } else {
                            targetElement.innerHTML = data.data.text;
                        }
                    }
                })
                .catch((error) => {
                    console.error("Error fetching task plans:", error);
                });
        });
    });

});