<?php


/**
 *  === HOW TO ADD NEW FONTS TO QUILL ===
 * 1. Add the necessary <link> tag in quill_render_css_links() to import the font.
 * 2. Add an <option> in quill_render_html_font_selector() to select the font in the Quill editor.
 * 3. Add the font name to the whitelist in quill_render_js_fonts().
 * 4. Add new #toolbar-container and .ql-font- entries in the corresponding sections of quill-fonts.css.
 */



/**
 * Renders the html link elements to import the necessary fonts for Quill.
 */
function quill_render_css_links($relativePath = "")
{
?>
<link rel="stylesheet" href="<?=$relativePath?>fonts/quill-fonts.css"/>
<link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Amatic+SC&display=swap&effect=anaglyph|emboss|fire-animation|neon|outline|shadow-multiple|3d|3d-float" rel="stylesheet"> <!-- Only to fetch font effects for this browser -->
    <link href="https://fonts.googleapis.com/css2?family=Amatic+SC&family=Boogaloo&family=Bungee+Shade&family=Caveat&family=Fredericka+the+Great&family=League+Script&family=Permanent+Marker&family=Source+Code+Pro&family=Thasadith&display=swap&effect=anaglyph|emboss|fire-animation|neon|outline|shadow-multiple|3d|3d-float" rel="stylesheet">
    <!--<link href="https://fonts.googleapis.com/css?family=Amatic+SC&family=Boogaloo&family=Bungee+Shade&family=Caveat&family=Fredericka+the+Great&family=League+Script&family=Permanent+Marker&family=Source+Code+Pro&family=Thasadith&display=swap&effect=anaglyph|emboss|fire-animation|neon|outline|shadow-multiple|3d|3d-float" rel="stylesheet">-->
<?php
}


/**
 * Renders the options to populate the html selector in the Quill toolbar.
 */
function quill_render_html_font_selector()
{
?>
    <!-- Default Quill fonts -->
    <option value="sans-serif" selected>Sans Serif</option>
    <option value="serif">Serif</option>
    <option value="monospace">Monospace</option>
    <!-- -- -->

    <!-- Font selection by Ines Correia -->
    <option value="amatic-sc">Amatic SC</option>
    <option value="bungee-shade">Bungee Shade</option>
    <option value="boogaloo">Boogaloo</option>
    <option value="caveat">Caveat</option>
    <option value="fredericka-the-great">Fredericka the Great</option>
    <option value="league-script">League script</option>
    <option value="permanent-marker">Permanent marker</option>
    <option value="source-code-pro">Source Code pro</option>
    <option value="thasadith">Thasadith</option>
    <!-- -- -->
<?php
}



/**
 * Render the javascript code needed to declare the whitelisted fonts.
 */
function quill_render_js_fonts()
{
?>

    //Fonts whitelist
    var Delta = Quill.import('delta');
    var Font = Quill.import('formats/font');

    Font.whitelist = [  'sans-serif',
                        'serif',
                        'monospace',

                        'amatic-sc',
                        'bungee-shade',
                        'boogaloo',
                        'caveat',
                        'fredericka-the-great',
                        'league-script',
                        'permanent-marker',
                        'source-code-pro',
                        'thasadith'
    ];

    Quill.register(Font, true);


    //Fonts sizes whitelist
    var fontSizeStyle = Quill.import('attributors/style/size');
    customSizes = ['0.75em', '1.5em', '2.5em', '4em'];
    fontSizeStyle.whitelist = fontSizeStyle.whitelist.concat(customSizes) ;
    Quill.register(fontSizeStyle, true);


    //Custom classes for Google Fonts effects

    let Inline = Quill.import('blots/inline');

    class OnFireBlock extends Inline
    {
        static create(value)
        {
            let node = super.create();
            node.setAttribute('class','font-effect-fire font-effect-fire-animation');
            return node;
        }
    }
    OnFireBlock.blotName = 'onfireblock';
    OnFireBlock.tagName = 'div';
    Quill.register(OnFireBlock);


    class NeonBlock extends Inline
    {
        static create(value)
        {
            let node = super.create();
            node.setAttribute('class','font-effect-neon');
            return node;
        }
    }
    NeonBlock.blotName = 'neon-block';
    NeonBlock.tagName = 'div';
    Quill.register(NeonBlock);


    class ThreeDBlock extends Inline
    {
        static create(value)
        {
            let node = super.create();
            node.setAttribute('class','font-effect-3d');
            return node;
        }
    }
    ThreeDBlock.blotName = '3d-block';
    ThreeDBlock.tagName = 'div';
    Quill.register(ThreeDBlock);


    class FloatingBlock extends Inline
    {
        static create(value)
        {
            let node = super.create();
            node.setAttribute('class','font-effect-3d-float');
            return node;
        }
    }
    FloatingBlock.blotName = '3d-float-block';
    FloatingBlock.tagName = 'div';
    Quill.register(FloatingBlock);


    class OutlineBlock extends Inline
    {
        static create(value)
        {
            let node = super.create();
            node.setAttribute('class','font-effect-outline');
            return node;
        }
    }
    OutlineBlock.blotName = 'outline-block';
    OutlineBlock.tagName = 'div';
    Quill.register(OutlineBlock);


    class ShadowMultipleBlock extends Inline
    {
        static create(value)
        {
            let node = super.create();
            node.setAttribute('class','font-effect-shadow-multiple');
            return node;
        }
    }
    ShadowMultipleBlock.blotName = 'shadow-multiple-block';
    ShadowMultipleBlock.tagName = 'div';
    Quill.register(ShadowMultipleBlock);


<?php
}




/**
 * Render other javascript scripts and functions needed to provide additional Quill funcitonality.
 */
function quill_render_js_scripts($relativePath = "")
{
?>
    <!--<script src="<?=$relativePath?>js/DynamicQuillTools/DynamicQuillTools.js"></script>-->
<?php
}
?>



<?php

function quill_render_font_effects_js($quill_var_name = "quill")
{
?>

    var fireBlockButton = document.querySelector('.ql-onfireblock');
    var neonBlockButton = document.querySelector('.ql-neon-block');
    var threeDBlockButton = document.querySelector('.ql-3d-block');
    var floatBlockButton = document.querySelector('.ql-3d-float-block');
    var outlineBlockButton = document.querySelector('.ql-outline-block');
    var shadowMultipleBlockButton = document.querySelector('.ql-shadow-multiple-block');


    fireBlockButton.addEventListener('click', function() {
        var range = quill.getSelection();
        if(range)
        {
            quill.formatText(range,'onfireblock');
        }
    });

    neonBlockButton.addEventListener('click', function() {
        var range = quill.getSelection();
        if(range)
        {
            quill.formatText(range,'neon-block');
        }
    });

    threeDBlockButton.addEventListener('click', function() {
        var range = quill.getSelection();
        if(range)
        {
            quill.formatText(range,'3d-block');
            //quill.formatText(range, 'color', 'white');
        }
    });

    floatBlockButton.addEventListener('click', function() {
        var range = quill.getSelection();
        if(range)
        {
            quill.formatText(range,'3d-float-block');
        }
    });

    outlineBlockButton.addEventListener('click', function() {
        var range = quill.getSelection();
        if(range)
        {
            quill.formatText(range,'outline-block');
        }
    });

    shadowMultipleBlockButton.addEventListener('click', function() {
        var range = quill.getSelection();
        if(range)
        {
            quill.formatText(range,'shadow-multiple-block');
        }
    });


<?php
}
?>