define(()=>{
  return (tokens,idx)=>{
    if((tokens[idx+2].type!=='link_close') || (tokens[idx+1].type!=='text')) return;
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DBA.SE') tokens[idx].attrSet('href','https://dba.stackexchange.com');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://TEX.SE') tokens[idx].attrSet('href','https://tex.stackexchange.com');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://M.SE') tokens[idx].attrSet('href','https://meta.stackexchange.com');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://META.SE') tokens[idx].attrSet('href','https://meta.stackexchange.com');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://UNIX.SE') tokens[idx].attrSet('href','https://unix.stackexchange.com');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://STATS.SE') tokens[idx].attrSet('href','https://stats.stackexchange.com');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DBA.TA') tokens[idx].attrSet('href','/databases');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DATABASES.TA') tokens[idx].attrSet('href','/databases');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://TEX.TA') tokens[idx].attrSet('href','/tex');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://UNIX.TA') tokens[idx].attrSet('href','/nix');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://NIX.TA') tokens[idx].attrSet('href','/nix');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://META.TA') tokens[idx].attrSet('href','/meta');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CODEGOLF.TA') tokens[idx].attrSet('href','/codegolf');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DOTNET.TA') tokens[idx].attrSet('href','/dotnet');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CSHARP.TA') tokens[idx].attrSet('href','/csharp');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://POWERSHELL.TA') tokens[idx].attrSet('href','/powershell');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://POSH.TA') tokens[idx].attrSet('href','/powershell');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://APL.TA') tokens[idx].attrSet('href','/apl');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://WEB.TA') tokens[idx].attrSet('href','/web');
    if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://PHP.TA') tokens[idx].attrSet('href','/php');
  };
});
