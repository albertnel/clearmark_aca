function OpenNode(id,type)
{
   var path = "lib/menu-tree/img";
   var num = id;
   var style = document.getElementById('dd'+num).style.display;
   if (style == "block")
   {
      document.getElementById('dd'+num).style.display = "none";
      if (type == 0)
         document.getElementById('plus'+id).src = path+"/plus.gif";
      else
         document.getElementById('plus'+id).src = path+"/plusbottom.gif";
   }
   else
   {
      document.getElementById('dd'+num).style.display = "block";
      if (type == 0)
         document.getElementById('plus'+id).src = path+"/minus.gif";
      else
         document.getElementById('plus'+id).src = path+"/minusbottom.gif";
   }
};

var prevChosen = "-1";

function selectNode(id)
{
   if (prevChosen != "-1")
      document.getElementById('sd'+prevChosen).className = "node";
   prevChosen = id;
   document.getElementById('sd'+id).className = "nodeSel";
};