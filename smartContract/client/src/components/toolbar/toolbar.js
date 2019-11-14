import React from 'react';
import './toolbar.css';
import AppBar from '@material-ui/core/AppBar';
import Toolbar from '@material-ui/core/Toolbar';
import Button from '@material-ui/core/Button';

class MenuBar extends React.Component {

  render() {
    const { classes } = this.props;

    return (
      <div>
        <AppBar elevation={0} className="nav" position="static">
          <Toolbar className="toolsContainer">
            Celdas Kickstarter
            <div style={{ flexGrow: 1 }}></div>
            <Button className="button" href="/proyectos" aria-label="Menu">
              Proyectos
            </Button>
            <Button className="button" href="/crear" aria-label="Menu">
              Crear Proyecto
            </Button>
          </Toolbar>
        </AppBar>
      </div>
    );
  }
}
export default MenuBar;