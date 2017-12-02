module.exports = function(content) {
  const { request, query, resource } = this;
  const idx = resource.indexOf('=');
  if (idx === -1) return content;
  const script = resource.substring(idx + 1);

  return `(function(next){
    var script = document.createElement( "script" )
    script.type = "text/javascript";

    // not available in some version of IE....
    script.onload = function() {
      next();
    };

    script.src = "${script}";
    document.getElementsByTagName( "head" )[0].appendChild(script);
  })(function(){
    ${content}
  })`;
};
