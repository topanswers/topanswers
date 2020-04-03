$(function() {
  $(window)
    .resize(
      _.debounce(function() {
        $("body").height(window.innerHeight);
      })
    )
    .trigger("resize");
  $("#join").click(function() {
    if (
      confirm(
        "This will set a cookie to identify your account. You must be 16 or over to join TopAnswers."
      )
    ) {
      $.post({
        url: "//post.topanswers.xyz/profile",
        data: { action: "new" },
        async: false,
        xhrFields: { withCredentials: true },
      })
        .done(function(r) {
          alert(
            "This login key should be kept confidential, just like a password.\nTo ensure continued access to your account, please record your key somewhere safe:\n\n" +
              r
          );
          location.reload(true);
        })
        .fail(function(r) {
          alert(
            r.status === 429
              ? "Rate limit hit, please try again later"
              : responseText
          );
          location.reload(true);
        });
    }
  });
  $("#link").click(function() {
    var pin = prompt("Enter PIN (or login key) from account profile");
    if (pin !== null) {
      $.post({
        url: "//post.topanswers.xyz/profile",
        data: { action: "link", link: pin },
        async: false,
        xhrFields: { withCredentials: true },
      })
        .fail(function(r) {
          alert(r.responseText);
        })
        .done(function() {
          location.reload(true);
        });
    }
  });
  $("#community").change(function() {
    window.location =
      "/" +
      $(this)
        .find(":selected")
        .attr("data-name");
  });
});
