var coll = document.getElementsByClassName("srg-collapsible");
var i;

for (i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
        this.classList.toggle("srg-active");
        var srg_content = this.nextElementSibling;
        if (this.classList.contains("srg-active")) {
            srg_content.style.maxHeight = srg_content.scrollHeight + "px";
        } else {
            srg_content.style.maxHeight = null;
        }
    });
}