/*
 * banner_ics plugin
 * @author pulsejet
 */

window.rcmail && rcmail.addEventListener('message', function(evt) {
    // Remove the button classes from the description links
    $('div.ics-event-description a').each(function () {
        const target = this;
        const removeClasses = () => $(target).removeClass('btn button btn-sm btn-primary');
        removeClasses();

        // Ugly: Larry adds the "button" class unpredictably,
        // so just observe the change and remove it
        if ("MutationObserver" in window) {
            new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        removeClasses();
                    }
                });
            }).observe(target, { attributes: true, childList: true, characterData: true });
        }
    });
});

