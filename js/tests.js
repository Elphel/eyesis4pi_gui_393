//global vars
var free_space_interval = false;
var master_ip = "163";
var logger_ip = "161"
var daemon_restart_en = true;
var intvl_temperatures;
var n = 8;
var working_intvl = false;
//var update_state_en = true;
var eyesis4pi_en = true;

var cams = [
  {"ip":"192.168.0.161","port":2326,"channel":3,"master":0,"logger":0},
  {"ip":"192.168.0.161","port":2325,"channel":2,"master":0,"logger":0},
  {"ip":"192.168.0.161","port":2323,"channel":0,"master":0,"logger":1},
  {"ip":"192.168.0.161","port":2324,"channel":1,"master":0,"logger":0},
  {"ip":"192.168.0.162","port":2326,"channel":3,"master":0,"logger":0},
  {"ip":"192.168.0.162","port":2325,"channel":2,"master":0,"logger":0},
  {"ip":"192.168.0.162","port":2323,"channel":0,"master":0,"logger":0},
  {"ip":"192.168.0.162","port":2324,"channel":1,"master":0,"logger":0},
  {"ip":"192.168.0.163","port":2325,"channel":2,"master":1,"logger":0},
  {"ip":"192.168.0.163","port":2326,"channel":3,"master":0,"logger":0}
];

function parseURL() {
  /*
  var parameters=location.href.replace(/\?/ig,"&").split("&");
  for (var i=0;i<parameters.length;i++) parameters[i]=parameters[i].split("=");
  for (var i=1;i<parameters.length;i++) {
    switch (parameters[i][0]) {
      case "n": n = parseInt(parameters[i][1]);break;
      case "master_ip": master_ip = parseInt(parameters[i][1]);break;
    }
  }
  */
  
  master_ip = cams[get_master_index()].ip;
  logger_ip = cams[get_logger_index()].ip;
  
}

function run(){
    working_intvl = setInterval("working()",1000);
    parseURL();
    test1_get_number_of_sensors();
}

function test7_color_bars_part1(){
    //color bars on
    set_parameter(master_ip,"TESTSENSOR",65544,true);
    //to JPEG
    set_parameter(master_ip,"COLOR",0,true);
    setTimeout("test7_color_bars_part2()",3000);
}

function test7_color_bars_part2(){
    //show pictures
    previews_init();
    refresh_images();
    //back to JP4
    set_parameter(master_ip,"COLOR",5,true);
    //color bars off
    set_parameter(master_ip,"TESTSENSOR",0,true);
    clearInterval(working_intvl);
    $("#status").html("Done"); 
}


function test6_t(){
    $("#test_6_div").html("<div id='temperatures_map'></div>");
    init_temperatures_table();
    read_temperatures();
    //more like a parallel launch
    test7_color_bars_part1();
}

function test5_imu(){
    $.ajax({
      url: "tests.php?cmd=imu&rq="+logger_ip,
      async: true,
      success: function(response){
	test5_update_imu(response);
      },
      contentType: "text/xml; charset=\"utf-8\""
    });      
}

function test5_update_imu(text){
    var html = "";
    var data = $(text).find("board");
    
    var imu = data.find("part").text();
    
    if (imu!="") html += "<div>IMU: OK ("+imu+")</div>\n";
    else         html += "<div class='alerts'>IMU not found</div>\n";
    
    $("#test_5_div").html(html);
    
    //next test
    test6_t();
}

function test4_gps(){
    $.ajax({
      url: "tests.php?cmd=gps&rq="+logger_ip,
      async: true,
      success: function(response){
	test4_update_gps(response);
      },
      contentType: "text/xml; charset=\"utf-8\""
    });    
}

function test4_update_gps(text){
  var html = "";
  var data = $(text).find("meta").find("frame").find("Exif");
  
  var gps_measuremode = data.find("GPSMeasureMode").text();
  var gps_datetime    = data.find("GPSDateTime").text();
  var gps_latitude    = data.find("GPSLatitude").text();
  var gps_longitude   = data.find("GPSLongitude").text();
  var gps_altitude    = data.find("GPSAltitude").text();
  
  if (gps_latitude=="") {
      html += "<div class='alerts'>GPS not found</div>";
  }else{  
      if (gps_latitude=='"0.000000"') {
	  html += "<div class='alerts'>GPS signal is not locked. GPS cold start can take up to 10 minutes, might not get locked indoors.</div>";
      }
      html += "<table>";
      html += "<tr><td>GPS measure mode</td><td>"+gps_measuremode+"</td></tr>";  
      html += "<tr><td>GPS date time</td><td>"+gps_datetime+"</td></tr>";  
      html += "<tr><td>Latitude</td><td>"+gps_latitude+"</td></tr>";  
      html += "<tr><td>Longitude</td><td>"+gps_longitude+"</td></tr>";
      html += "<tr><td>Altitude</td><td>"+gps_altitude+"</td></tr>";  
      html += "</table>";
  }
  
  html += "<br/><span style='font-size:14px;'>Manual test: check the coordinates <a href='http://"+logger_ip+":2323/meta'>here</a>.</span>";
  
  $("#test_4_div").html(html);

  //run the next test
  test5_imu();
}

function test3_internal_cf_cards(){
    
    rqstr = "";
    tmparr = get_unique_ips();
    for(var i=0;i<tmparr.length;i++){
        if (i!=0) rqstr += ",";
        rqstr += tmparr[i];
    }
    
    $.ajax({
      url: "tests.php?cmd=ssds&rq="+rqstr,
      async: true,
      success: function(response){
	test3_update_cards(response);
      },
      contentType: "text/xml; charset=\"utf-8\""
    });    
}

function get_unique_ips(){
    res = [];
    for(var i=0;i<cams.length;i++){
         if (res.indexOf(cams[i].ip)==-1) {
             res.push(cams[i].ip);
         }
    }
    return res;
}

function test3_update_cards(text){
  var html = "";
  var data = $(text).find("Document");

  var sda1;
  var sdb1;
  var tmp_ip;
  
  data.find("cam").each(function(){
      tmp_ip  = $(this).find("ip").text();
      sda = $(this).find("sda").text();
      
      html += "<td>"+tmp_ip+"</td>\n";
      
      if (sda=="1") html += "<td align='center'><div class='square green'></div></td>\n";
      else           html += "<td></td>\n";
      
      html = "<tr>"+html+"</tr>";
  });
  
  html += "<table><tr><td></td><td style='padding:7px;'>sda</td></tr>"+html+"</table>";
  
  $("#test_3_div").html(html);
  
  //run the next test
  test4_gps();
}

function test1_get_number_of_sensors(){
    var rq_str = "";
    for(var i=0;i<cams.length;i++){
        if (i!=0){
            rq_str += ",";
        }
        rq_str += cams[i].ip+":"+cams[i].channel;
    }
        
    $.ajax({
      url: "eyesis4pi_get_sensors.php?rq="+rq_str,
      async: true,
      success: function(response){
	test1_update_cams(response);
      },
      contentType: "text/xml; charset=\"utf-8\""
    });
}

function test1_update_cams(text){
  var html = "";
  var data = $(text).find("Document");
  var nostereo = false;
  
  html += "<table class='cam_table'>";
  html += "<tr><td style='padding:0px 30px 0px 30px;'>Module</td><td>Port</td><td>up</td><td>mid</td><td>bot</td><tr/>";  
  html += "</table>";
  html += "<table class='cam_table'>";
    
  var i = 0;
  
  data.find("cam").each(function(){
    for(var j=0;j<3;j++) {
        if ($(this).find("error").text()==1){
            html += "</table>";
            html += "<div class='alerts'>Alert: camera "+(i+1)+" is offline. Check the LEDs on the switch. Wait a little then refresh this page</div>";
            html += "<table class='cam_table'>";
            break;   
        }else{
            if (j==0) {
                html += "<tr>";
                html += "<td>"+cams[i].ip+"</td>";
                html += "<td style='padding:0px 20px 0px 23px;'>"+cams[i].channel+"</td>";
            }
            
            res0 = $(this).find("chn0").text();
            res = $(this).find("chn"+(j+1)).text();
            
            if (res=='1') {
                html += "<td><div class='square green'></div></td>";
            }else{
                if (res0==1){
                    if (j==1) html += "<td><div class='square green'></div></td>";
                    else      html += "<td>-</td>";
                }else{
                    html += "<td>-</td>";
                }
            }
            
            if (j==2) html += "</tr>";
        }
    }
    i++;
  });
    
  html += "</table>";
  
  $("#test_1_div").html(html);
  
  //run test2
  //clearInterval(working_intvl);
  test3_internal_cf_cards();
}

function set_parameter(ip,par,val,async,callback){
  //console.log("n is "+n);
  var url = "eyesis4pi_control.php?set_parameter&rq="+cams_to_str()+"&pname="+par+"&pvalue="+val;
  
  $.ajax({
    url: url,
    async: async,
    success: function(data){
      //$("#status").html(data);
      if (typeof callback != 'undefined') callback();
    }
  }); 
}

function refresh_images(){
  var d = new Date();
  var curr_time = d.getTime();
  
  var pic = new Object();
  
  for(var i=0;i<cams.length;i++){
    
    pic[i] = new Image();
    pic[i].src = "http://"+cams[i].ip+":"+cams[i].port+"/bimg?"+curr_time;
    pic[i].index = i;
    pic[i].onload = function(){

      var w = 200;
      var h = 150;
      var W = 2592;
      var H = 1944;

      var cnv = document.getElementById("cam_"+this.index+"_canvas");
      var cContext = cnv.getContext('2d');
      cnv.setAttribute('width',h);cnv.setAttribute('height',3*w);
      cContext.rotate(90*Math.PI/180);
      
      var k = 3;
      
      //mask out 2s
      //if ((this.index==0)||(this.index==1)||(this.index==7)) k = 3;
      
      if (this.index%2==0) {
        cContext.drawImage(this, 0,0*H,W,H, 0*w,-1*h,w,h);
        cContext.drawImage(this, 0,1*H,W,H, 1*w,-1*h,w,h);
        cContext.scale(-1,1);
        cContext.drawImage(this, 0,2*H,W,H, -3*w, -1*h, w, h);
      }else{
	  cContext.scale(1,-1); //mirror is needed
	  cContext.drawImage(this, 0,0*H,W,H, 0*w,0*h,w,h);
	  cContext.drawImage(this, 0,1*H,W,H, 1*w,0*h,w,h);
          cContext.scale(-1,1);
	  cContext.drawImage(this, 0,2*H,W,H, -3*w,h*(0),w,h);
      }
      
    };
  }
}

function previews_init(){
  
  var c = "<table id='prevs_images'><tr>\n";
  
  for (var i=0;i<cams.length;i++) {
      c += "<td><canvas id='cam_"+i+"_canvas' class='prevs'></canvas></td>";
  }
  
  c+= "</tr></table>";
  
  $("#test_7_acquired").html(c);
  $("#test_7_reference").html("<img src='pictures/color_bars_test"+n+".jpeg' width='"+(116*n)+"' \>");
}

function working(){
    var d = new Date();

    var curr_sec = d.getSeconds();
    
    if      (curr_sec%4==1) $("#status").html("Working.");
    else if (curr_sec%4==2) $("#status").html("Working..");
    else if (curr_sec%4==3) $("#status").html("Working...");
    else                    $("#status").html("Working");
}

/*
function parseURL() {
  var parameters=location.href.replace(/\?/ig,"&").split("&");
  for (var i=0;i<parameters.length;i++) parameters[i]=parameters[i].split("=");
  for (var i=1;i<parameters.length;i++) {
    switch (parameters[i][0]) {
      case "n": n = parseInt(parameters[i][1]);break;
      case "triclops" : triclops_init();break;
      case "master_ip" : master_ip = parseInt(parameters[i][1]);
    }
  }
  if (n!=8&&n!=9) {eyesis4pi_en = false};
  console.log("eyesis4pi_en = "+eyesis4pi_en+" n = "+n);
}
*/