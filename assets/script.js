(function() {
	'use strict';

	const main = document.getElementById('main');
	const aside = document.getElementById('aside');
	const bigPicture = document.getElementById('bigPicture');
	const bigCaption = document.getElementById('bigCaption');
	var index = 0;

	const xhr = new XMLHttpRequest();
	xhr.onload = function() {
		const datas = JSON.parse(this.responseText);
		if('items' in datas) {
			datas.items.forEach(function (item, i) {
				const el = document.createElement('FIG');
				el.innerHTML = `<span>&nbsp;</span>
<img id="img-${i}" src="theme-${item.name}/apercu_min.jpg" width="${item.w}" height="${item.h}" alt="" title="${item.name}" data-preview="${item.preview}" />
<figcaption><a href="theme-${item.name}/archive.zip" download="theme-${item.name}.zip" title="download">${item.name}</a></figcaption>
`;

				if('title' in item) {
					const infos = document.createElement('SPAN');
					infos.textContent = 'infos.xml';
					infos.className = 'infos';
					el.appendChild(infos);
				}

				if('descr' in item) {
					const caption = el.querySelector('figcaption');
					const bullet = document.createElement('SPAN');
					bullet.textContent = '?';
					bullet.setAttribute('data-content', [item.descr, item.author, item.date].join(' - '));
					bullet.className = 'descr';
					caption.appendChild(bullet);
				}
				main.appendChild(el);
			});

			document.getElementById('themesCounter').textContent =  datas.items.length + ' thèmes';

			document.getElementById('main').onclick = function(event) {
				if(event.target.tagName == 'IMG') {
					event.preventDefault();
					index = parseInt(event.target.id.replace(/^img-/, ''));
					bigPicture.src = event.target.src.replace(/apercu_min.jpg/, event.target.dataset.preview);
					bigCaption.textContent = (index + 1) + ': ' + event.target.title;
					bigCaption.href = 'theme-' + event.target.title + '/archive.zip';
					bigCaption.download = 'theme-' + event.target.title + '.zip';
					aside.classList.add('active');
				}
			}

			document.getElementById('close').onclick = function(event) { aside.classList.remove('active'); }

			document.getElementById('prev').onclick = function(event) {
				if(index <= 0) {
					this.classList.add('forbidden');
					return;
				}
				index--;
				document.getElementById('img-' + index).click()
				if(index <= 0) {
					this.classList.add('forbidden');
				} else {
					this.classList.remove('forbidden');
				}
			}

			document.getElementById('next').onclick = function(event) {
				const maxIndex = datas.items.length - 1;
				if(index >= maxIndex) {
					return;
				}
				index++;
				document.getElementById('img-' + index).click()
				if(index >= maxIndex) {
					this.classList.add('forbidden');
				} else {
					this.classList.remove('forbidden');
				}
			}

			document.getElementById('up').onclick = function (event) {
				event.preventDefault();
				aside.classList.remove('active');
				document.getElementById('top').scrollIntoView({behavior: 'smooth'});
			}

		}
	}

	xhr.open('GET', 'assets/themes.json');
	xhr.send();
})();
