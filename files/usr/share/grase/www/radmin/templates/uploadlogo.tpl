{include file="header.tpl" Name="Upload Logo" activepage="uploadlogo"}

{include file="errors.tpl"}

<h2>{t}Upload Logo{/t}</h2>

<div id="LogoForm">
<form method="post" action="" class="generalForm" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="50960" />
    <div>
        <label for='newlogo'>{t}Logo Image{/t}</label>
        <input type="file" name="newlogo" />        
        <span>{t}Select new logo file.{/t} {t}Logo file needs to be in png format, and relatively small (remember, every page has the logo). 10 Kilobytes maximum. It's physical size shouldn't be bigger than about 220px in both directions.{/t}</span><br/>
        <img src="/grase/images/logo.png" alt="Logo"/>                
    </div>
   
    <button type="submit" name="newlogosubmit">{t}Upload Logo{/t}</button> 

</form>


</div>

{include file="footer.tpl"}