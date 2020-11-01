/*
 * HTML
 * <select id="servers"></select>
 * <div id="graphbuttons"></div>
 * <div id="graph"></div>
 *
 * Require: jQuery
 */

const defaultHost = 'Raspi';
const defaultGraph = '温度 湿度';
let json = [];

$.get('api.php', (response) => {
    json = JSON.parse(response);
    setServersSelect();
    changeGraphButtons();
});

$('#servers').change(() => {
    changeGraphButtons();
});

function setServersSelect(){
    $('#servers').empty();
    json.forEach((val) => {
        const optionElement = $('<option>', {
            text: val.name
        });
        if(val.name === defaultHost){
            optionElement.attr('selected', true);
        }
        $('#servers').append(optionElement);
    });
}

function changeGraphButtons(){
    $('#graph').empty();
    $('#graphbuttons').empty();
    const selectedServer = $('#servers option:selected').text();
    json.forEach((val) => {
        if(selectedServer !== val.name){
            return;
        }
        val.graphs.forEach((graph) => {
            const buttonElement = $('<a>', {
                class: 'button',
                onclick: 'clickGraphButton(this)',
                text: graph.name,
                'data-graphtype': graph.graphtype,
                'data-graphid': graph.graphid
            });
            if(selectedServer === defaultHost && graph.name === defaultGraph){
                buttonElement.addClass('primary');
                clickGraphButton(buttonElement);
            }
            $('#graphbuttons').append(buttonElement);
        });
    });
}

let currentGraphType = undefined;
let currentGraphId = undefined;
function clickGraphButton(button){
    $('#graphbuttons .primary').removeClass('primary');
    $(button).addClass('primary');
    currentGraphType = $(button).attr('data-graphtype');
    currentGraphId = $(button).attr('data-graphid');
    loadGraphImage();
    setGraphImageTimer();
}

function loadGraphImage(){
    const url = `api.php?type=chart&graphtype=${currentGraphType}&graphid=${currentGraphId}&time=${Date.now()}`;
    const aElement = $('<a>', {
        href: url,
        target: '__blank'
    });
    const imgElement = $('<img>', {
        src: url
    });
    aElement.append(imgElement);
    $('#graph').empty().append(aElement);
}

let interval = undefined;
function setGraphImageTimer(){
    if(interval !== undefined){
        clearInterval(interval);
    }
    interval = setInterval(() => {
        if(currentGraphType !== undefined || currentGraphId !== undefined){
            loadGraphImage();
        }
    }, 5 * 60 * 1000);
}
