class QuestionPost {
    constructor(post) {
        this.post = post;
        this.type = this.post.post_type;
    }

    createElement(index = 0) {
        this.index = index;
        this.element = document.createElement('div');

        this.element.className = `feedItem feedItem--form hidden`;
        this.element.style.animationDelay = `${index * 50}ms`
        this.element.innerHTML = this.generateDefaultHtml();

        this.addEventListeners();

        return this.element;
    }

    addEventListeners() {
        // open form
        this.element.querySelector('.feedItem__teaser-button').addEventListener('click', this.toggleFormHandler.bind(this));
        this.element.querySelector('.hide-form-button').addEventListener('click', this.toggleFormHandler.bind(this));
        this.element.querySelector('.user-submit-form').addEventListener('submit', this.userFormSubmitHandler.bind(this));
        this.element.querySelector('[name=content]').addEventListener('input', this.userFormContentInputHandler);
        this.element.querySelector('[name=media-type]').addEventListener('change', this.userFormMediaTypeChangeHandler);
        this.element.querySelector('[name=image]').addEventListener('change', this.userFormImageChangeHandler);
    }

    generateDefaultHtml() {
        return `
            <div class="feedItem__body">
                <div class="feedItem__form-teaser">
                    <h3>DU</h3>
                    <button class="feedItem__teaser-button">Hier kannst Du posten!</button>
                </div>
                ${this.generateFormHtml()}
                ${this.generateSuccessHtml()}
            </div>
        `;
    }

    generateFormHtml() {
        const index = this.index;

        return `
            <form class='user-submit-form' id='user-submit-form-${index}' data-id="${index}" enctype="multipart/form-data">
                <button class="hide-form-button" type="button">
                    <i class="fa fa-close"></i>
                </button>
                <label for='content-${index}'>Text (<span class='content-chars'>0</span> von 280 Zeichen)</label>
                <textarea class='field' name='content' id='content-${index}' rows='10' maxlength='280'></textarea>

                <label for='media-type-${index}'>Bild oder YouTube-Video</label>
                <select class='field' name='media-type' id='media-type-${index}' data-id='${index}'>
                    <option value='image'>Bild</option>
                    <option value='youtube'>YouTube</option>
                </select>
                
                <div class='image-wrapper' id='image-wrapper-${index}'>
                    <label id='image-label-${index}'>Bitte lade eine Bilddatei hoch</label>
                    <input type='file' accept='image/*' name='image' id='image-${index}' style='display: none;'/>
                    <input class='image-button' type='button' value='Bild auswählen...' onclick='document.getElementById(\"image-${index}\").click();' />
                </div>

                <div class='youtube-wrapper' id='youtube-wrapper-${index}' style='display: none;'>
                    <label id='youtube-label-${index}'>Bitte füge einen YouTube-Link ein</label>
                    <input type='url' name='youtube' id='youtube-${index}' placeholder='https://www.youtube.com/watch?v=HZhFC11uB3Q' />
                </div>

                <!-- <label htmlFor='check'>Ich versichere, dass ich alle Rechte an dem Text und/oder Bild habe und diese auf unserer Webseite und unseren Social Media Kanälen veröffentlicht werden dürfen.</label>
                <input type='checkbox' name='check' id='check' /> -->

                <div class="error-message">
                    Bitte schreibe einen Text (maximal 280 Zeichen), lade ein Bild hoch oder verlinke ein YouTube-Video!
                </div>

                <button type='submit'>Senden!</button>
            </form>
        `;
    }

    generateSuccessHtml() {
        return `
            <div class="feedItem__success-message">
                <h3>Vielen Dank für Deinen Beitrag!</h3>
                <h4>Deine Eingabe wird von unseren Mitarbeiten überprüft und zeitnah veröffentlicht.</h4>
            </div>
        `;
    }

    toggleFormHandler() {
        this.element.classList.toggle('hidden');
        this.element.classList.remove('show-error');
    }

    userFormSubmitHandler(e) {
        e.preventDefault();
        const target = e.currentTarget || e.srcElement;
        const data = new FormData(target);

        if (this.validate(data)) {
            this.element.classList.remove('show-error');
            this.element.classList.add('sending');

            data.set('action', 'user_post_submit'); // request handler name
            
            fetch(PHP_VARS.AJAX_URL, {
                credentials: 'include', // ms edge fix
                method: 'POST',
                body: data,
            })
            .then(res => res.text())
            .then(res => {
                console.log(res);
                this.element.classList.remove('sending');
            })
            .catch(err => {
                console.log('ERROR:', err);
                this.element.classList.remove('sending');
            });
        } else {
            this.element.classList.add('show-error');
        }
    }

    validate(formData) {
        const content = formData.get('content');
        const mediaType = formData.get('media-type');
        const image = formData.get('image');
        const youtube = formData.get('youtube');

        return (
            (content.length > 0 && content.length < 280) ||
            (mediaType === 'image' && image.size > 0) ||
            (mediaType === 'youtube' && youtube.length > 0)
        );
    }
    
    userFormMediaTypeChangeHandler(e) {
        const target = e.currentTarget || e.srcElement;
        const value = target.value;
        const id = target.dataset.id;
    
        const youtube = document.getElementById(`youtube-wrapper-${id}`);
        const image = document.getElementById(`image-wrapper-${id}`);
    
        if (value === 'youtube') {
            youtube.style.display = 'block';
            image.style.display = 'none';
            
        } else if (value === 'image') {
            image.style.display = 'block';
            youtube.style.display = 'none';
        }
    }
    
    userFormContentInputHandler(e) {
        const target = e.currentTarget || e.srcElement;
        target.parentNode.querySelector('.content-chars').textContent = target.value.length;
    }
    
    userFormImageChangeHandler(e) {
        const target = e.currentTarget || e.srcElement;
        const value = target.value;
        const imageButton = target.nextElementSibling;
    
        if (value === '') {
            imageButton.value = 'Bild auswählen...';
        } else {
            const fileNameParts = value.split('\\');
            imageButton.value = fileNameParts[fileNameParts.length - 1];
        }
    }
}

module.exports = QuestionPost;