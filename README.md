# web-sdk-conference-samples
Sample demo Stringee video conference
## Demo
https://v2.stringee.com/web-sdk-conference-samples/test_room.html

### Step 1: Chuẩn bị

1. Để sử dụng Stringee Video Api thì bạn phải có 1 tài khoản Stringee. Nếu chưa có tài khoản Stringee, thì thực hiện đăng ký tài khoản tại https://developer.stringee.com/account/register
2. Tạo 1 project trên https://developer.stringee.com/project




### Step 2: Api quản lý room
0. Sinh access token để gọi rest api theo hướng dẫn https://developer.stringee.com/docs/call-rest-api/call-rest-api-authentication
(Để test có thể truy cập vào Dashboard -> Tools -> Generate Access token. (Authentication: chọn Rest API Authentication)


1. Tạo room (thay ACCESS_TOKEN = access token sinh ra ở bước 0)
```
curl -i -X POST \
   -H "Content-Type:application/json" \
   -H "X-STRINGEE-AUTH:ACCESS_TOKEN" \
   -d \
'{
  "name": "tient_test",
  "uniqueName": "tient_test"
}' \
 'https://api.stringee.com/v1/room2/create'
 ```
 
2. List room
```
 curl -i -X GET \
   -H "X-STRINGEE-AUTH:ACCESS_TOKEN" \
 'https://api.stringee.com/v1/room2/list'
```

3. Delete room
```
curl -i -X PUT \
   -H "X-STRINGEE-AUTH:ACCESS_TOKEN" \
   -H "Content-Type:application/json" \
   -d \
'{
  "roomId": "room-vn-1-EKNUBSUJFW-1597685448625"
}' \
 'https://api.stringee.com/v1/room2/delete'
```




### Step 3. Tích hợp sdk vào web

0. Sinh roomtoken với định dạng jwt như sau gửi về cho client để thực hiện join room (chi tiết tham khảo file php\token_pro.php ở sample đính kèm)

```
HEADER:
    {
        "typ": "JWT",
        "alg": "HS256",// only support HS256
        "cty": "stringee-api;v=1"
    }

PAYLOAD:
    {
        "jti": "SK...-...",//JWT ID
        "iss": "SK...",//API key sid
        "exp": ...,//expiration time
        "roomId": ""room-...", //Your room id
		'permissions' => array(
            'publish' => true, // allow publish
            'subscribe' => true,//allow subcribe
            'control_room' => true, //allow control
        )
    }

VERIFY SIGNATURE:
    HMACSHA256(
        base64UrlEncode(HEADER) + "." +
        base64UrlEncode(PAYLOAD),
        apiKeySecret
    )
    
```
	
1. Add Stringee Sdk vào source của bạn
```
<script type="text/javascript" src="js/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="https://cdn.stringee.com/sdk/web/latest/stringee-web-sdk.min.js"></script>
```


2. Sinh access token để connect vào StringeeServer
- Cho mục đích test, bạn có thể truy cập  Dashboard -> Tools -> Generate Access token and generates an access_token.
- Cho mục đích production: tham khảo https://developer.stringee.com/docs/client-authentication


3. Kết nối
```
var stringeeClient;
client.connect(access_token);
```

Các sự kiện
- Khi kết nối tới StringeeServer
```
client.on('connect', function () {
    console.log('connected');
});
```

- Khi xác  thực thành công 
```
client.on('authen', function (res) {
    console.log('authen', res);
    $('#loggedUserId').html(res.userId);
});
```

- Khi disconnect với StringeeServer
```
client.on('disconnect', function () {
    console.log('disconnected');
});
```



4. Join room
```
var videoDimensions = $('#videoDimensions').val();
console.log('videoDimensions: ' + videoDimensions);
if (videoDimensions == '720p') {
	videoDimensions = {
		width: {
			min: "1280",
			max: "1280"
		},
		height: {
			min: "720",
			max: "720"
		}
	};
} else if (videoDimensions == '480p') {
	videoDimensions = {
		width: {
			min: "854",
			max: "854"
		},
		height: {
			min: "480",
			max: "480"
		}
	};
} else if (videoDimensions == '360p') {
	videoDimensions = {
		width: {
			min: "640",
			max: "640"
		},
		height: {
			min: "360",
			max: "360"
		}
	};
} else if (videoDimensions == '240p') {
	videoDimensions = {
		width: {
			min: "426",
			max: "426"
		},
		height: {
			min: "240",
			max: "240"
		}
	};
}

var pubOptions = {
	audio: true,
	video: true,
	screen: screenSharing,//screenSharing = true nếu là share màn hình, = false nếu người dùng join room
	videoDimensions: videoDimensions
};

StringeeVideo.createLocalVideoTrack(stringeeClient, pubOptions).then(function (localTrack1) {
	console.log('create Local Video Track success: ', localTrack1);
	localTracks.push(localTrack1);

	//play local video
	var videoElement = localTrack1.attach();
	videoElement.setAttribute("style", "width: 300px;background: black;padding: 5px;height: 200px;margin: 5px");
	videoElement.setAttribute("controls", "true");
	videoElement.setAttribute("playsinline", true);
	document.body.appendChild(videoElement);

	StringeeVideo.joinRoom(stringeeClient, roomToken).then(function (data) {
		console.log('join room success data: ', data);
		$('#shareScreenBtn').removeAttr('disabled');
		$('#leaveBtn').removeAttr('disabled');

		$('#muteBtn').removeAttr('disabled');
		$('#disableVideoBtn').removeAttr('disabled');

		$('#joinBtn').attr('disabled', 'disabled');

		room = data.room;

		//room events
		room.clearAllOnMethos();
		
		//Su kien joinroom
		room.on('joinroom', function (event) {
			console.log('on join room: ' + JSON.stringify(event.info));
		});
		
		//Su kien leave room
		room.on('leaveroom', function (event) {
			console.log('on leave room: ' + JSON.stringify(event.info));
		});
		
		//Su kien leave room
		room.on('message', function (event) {
			console.log('on message: ' + JSON.stringify(event.info));
		});
		
		//Su kien addtrack khi co them nguoi khac join room
		room.on('addtrack', function (event) {
			console.log('on add track: ' + JSON.stringify(event.info));
			var local = false;
			localTracks.forEach(function (localTrack2) {
				if (localTrack2.serverId === event.info.track.serverId) {
					console.log(localTrack2.serverId + ' is LOCAL');
					local = true;
				}
			});
			if (!local) {
				subscribe(event.info.track);
			}
		});
		
		//Su kien removetrack khi co them nguoi khac leave room
		room.on('removetrack', function (event) {
			console.log('on remove track', event);
			var track = event.track;
			if (!track) {
				return;
			}

			var mediaElements = track.detach();
			mediaElements.forEach(function (videoElement) {
				videoElement.remove();
			});
		});

		//publish Track cua chinh minh vao room
		room.publish(localTrack1).then(function () {
			console.log('publish Local Video Track success: ' + localTrack1.serverId);
		}).catch(function (error1) {
			console.log('publish Local Video Track ERROR: ', error1);
		});

		//subscribe video cua nhung nguoi join room truoc
		data.listTracksInfo.forEach(function (trackInfo) {
			subscribe(trackInfo);
		});
	}).catch(function (res) {
		console.log('join room ERROR: ', res);
	});
}).catch(function (res) {
	console.log('create Local Video Track ERROR: ', res);
	showStatus(res.name + ": " + res.message);
});
```
