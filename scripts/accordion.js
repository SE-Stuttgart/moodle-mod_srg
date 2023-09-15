var collapseButtons = document.getElementsByClassName("mod_srg-collapse-button");

for (var i = 0; i < collapseButtons.length; i++) {
    var button = collapseButtons[i];
    button.addEventListener('click', function() {
        var iconTarget = this.getAttribute('icon-target');
        var iconElement = document.querySelector(iconTarget);

        if (iconElement) {
            iconElement.classList.toggle('fa-chevron-down');
            iconElement.classList.toggle('fa-chevron-up');
        }
    });
}