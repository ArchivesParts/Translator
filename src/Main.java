
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 * @author Gregory ANNE <gregonmac@gmail.com>
 */
public class Main {

    private static final Logger LOG = Logger.getLogger(Main.class.getName());

    public static void main(String[] argv) {

        Editor editor = new Editor();
        editor.setVisible(true);

        Translator translator = new Translator(editor);
//        translator.addTranslationFile("en_BG", new File("/Users/greg/Dropbox/Projects/Translator/src/en_GB.csv"));
        translator.addTranslationFile("fr_FR", new File("/Users/greg/Dropbox/Projects/Translator/src/fr_FR.csv"));

        List<String> translations;
        try {
//            translator.processFolder(new File("/Users/greg/Dropbox/Projects/Translator/test/"));
            translator.processFolder(new File("/Users/greg/Digitaleo/v2.marketeo.net/application/"));
        } catch (FileNotFoundException ex) {
            Logger.getLogger(Main.class.getName()).log(Level.SEVERE, null, ex);
        } catch (IOException ex) {
            Logger.getLogger(Main.class.getName()).log(Level.SEVERE, null, ex);
        }
    }
}
